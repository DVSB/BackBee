<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 * 
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\ClassContent\Repository;

use BackBuilder\NestedNode\Page,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\ContentSet,
    BackBuilder\Security\Token\BBUserToken,
    BackBuilder\BBApplication;
use Doctrine\ORM\Tools\Pagination\Paginator,
    Doctrine\ORM\Query,
    Doctrine\ORM\Query\ResultSetMapping,
    Doctrine\ORM\Query\ResultSetMappingBuilder,
    Doctrine\ORM\EntityRepository;

/**
 * AClassContent repository
 * 
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @subpackage  Repository\Element
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class ClassContentRepository extends EntityRepository
{

    /**
     * Get all content uids owning the provided content
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return array
     */
    public function getParentContentUid(AClassContent $content)
    {
        $q = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('c.parent_uid')
                ->from('content_has_subcontent', 'c')
                ->andWhere('c.content_uid = :uid')
                ->setParameter('uid', $content->getUid());

        return $q->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Replace root contentset for a page and its descendants
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\ClassContent\ContentSet $oldContentSet
     * @param \BackBuilder\ClassContent\ContentSet $newContentSet
     * @param \BackBuilder\Security\Token\BBUserToken $userToken
     */
    public function updateRootContentSetByPage(Page $page, ContentSet $oldContentSet, ContentSet $newContentSet, BBUserToken $userToken)
    {
        $em = $this->_em;
        $q = $this->createQueryBuilder("c");
        $results = $q->leftJoin("c._pages", "p")
                        ->leftJoin("c._subcontent", "subcontent")
                        ->where("subcontent = :contentToReplace")
                        ->andWhere("p._leftnode > :cpageLeftnode")
                        ->andWhere("p._rightnode < :cpageRightnode")
                        ->setParameters(array(
                            "contentToReplace" => $oldContentSet,
                            "cpageLeftnode" => $page->getLeftnode(),
                            "cpageRightnode" => $page->getRightnode()
                        ))
                        ->getQuery()->getResult();

        if ($results) {
            foreach ($results as $parentContentSet) {
                /* create draft for the main container */
                if (NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($parentContentSet, $userToken, true)) {
                    $parentContentSet->setDraft($draft);
                }
                /* Replace the old ContentSet by the new one */
                $parentContentSet->replaceChildBy($oldContentSet, $newContentSet);
                $em->persist($parentContentSet);
            }
        }
    }

    /**
     * Get a selection of ClassContent
     * @param array $selector
     * @param boolean $multipage
     * @param boolean $recursive
     * @param int $start
     * @param int $limit
     * @param boolean $limitToOnline
     * @param boolean $excludedFromSelection
     * @param array $classnameArr
     * @param int $delta
     * @return array|Paginator
     */
    public function getSelection($selector, $multipage = false, $recursive = true, $start = 0, $limit = null, $limitToOnline = true, $excludedFromSelection = false, $classnameArr = array(), $delta = 0)
    {

        $q = $this->createQueryBuilder('c')->setParameters(array());
        $q->distinct();
        if ($excludedFromSelection) {
            $q->leftJoin('c._indexation', 'iselect', 'WITH', 'iselect._field = \'excluded-from-selection\'')
                    ->andWhere('iselect._value IS NULL');
        }

        if (array_key_exists('criteria', $selector)) {
            $criteria = (array) $selector['criteria'];
            foreach ($criteria as $field => $crit) {
                $crit = (array) $crit;
                if (1 == count($crit))
                    $crit[1] = '=';

                $alias = uniqid('i');
                $q->leftJoin('c._indexation', $alias)
                        ->andWhere($alias . '._field = :field' . $alias)
                        ->andWhere($alias . '._value ' . $crit[1] . ' :value' . $alias)
                        ->setParameter('field' . $alias, $field)
                        ->setParameter('value' . $alias, $crit[0]);
            }
        }
        $nodes = NULL;
        if (array_key_exists('parentnode', $selector)) {
            $q->leftJoin('c._mainnode', 'p');
            if (1 == count($selector['parentnode'])) {
                $nodes = array($this->_em->find('BackBuilder\NestedNode\Page', $selector['parentnode'][0]));
            } else {
                if (is_array($selector['parentnode']) && count($selector['parentnode'])) {
                    $nodes = $this->_em->getRepository('BackBuilder\NestedNode\Page')->findBy(array('_uid' => $selector['parentnode']));
                }
            }
            if (null != $nodes) {

                foreach ($nodes as $node) {
                    if ($recursive) {
                        $q->andWhere('p._root = :root_' . $node->getUid())
                                ->andWhere('p._leftnode >= :leftnode_' . $node->getUid())
                                ->andWhere('p._rightnode <= :rightnode_' . $node->getUid())
                                ->setParameter('root_' . $node->getUid(), $node->getRoot())
                                ->setParameter('leftnode_' . $node->getUid(), $node->getLeftnode())
                                ->setParameter('rightnode_' . $node->getUid(), $node->getRightnode());
                    } else {
                        $q->andWhere('p._parent = :parent_' . $node->getUid())
                                ->setParameter('parent_' . $node->getUid(), $node);
                    }
                }

                if ($limitToOnline) {
                    $q->andWhere('p._state IN (:states)')
                            ->andWhere('p._publishing IS NULL OR p._publishing <= CURRENT_TIMESTAMP()')
                            ->andWhere('p._archiving IS NULL OR p._archiving > CURRENT_TIMESTAMP()')
                            ->setParameter('states', array(Page::STATE_ONLINE, Page::STATE_ONLINE | Page::STATE_HIDDEN));
                } else {
                    $q->andWhere('p._state < :statedeleted')
                            ->setParameter('statedeleted', Page::STATE_DELETED);
                }
            }
        }

//        /* get online only content */
//        if ($limitToOnline) {
//            $q->andWhere('p._state IN (:states)')
//                    ->setParameter('states', array(Page::STATE_ONLINE, Page::STATE_ONLINE | Page::STATE_HIDDEN));
//        } else {
//            $q->andWhere('p._state < :statedeleted')
//                    ->setParameter('statedeleted', Page::STATE_DELETED);
//        }


        /* filter by classcontents */
        if (!array_key_exists('orderby', $selector)) {
            $selector['orderby'] = array('created', 'desc');
        } else {
            $selector['orderby'] = (array) $selector['orderby'];
        }
        /* handle keywords here */
        if (array_key_exists("keywordsselector", $selector)) {
            $keywordInfos = $selector["keywordsselector"];
            if (is_array($keywordInfos)) {
                if (array_key_exists("selected", $keywordInfos)) {
                    $selectedKeywords = $keywordInfos["selected"];
                    if (is_array($selectedKeywords) && !empty($selectedKeywords)) {
                        $contentIds = $this->_em->getRepository("BackBuilder\NestedNode\KeyWord")->getContentsIdByKeyWords($selectedKeywords);
                        if (is_array($contentIds) && !empty($contentIds)) {
                            $q->andWhere("c._uid in(:kwContent)")->setParameter("kwContent", $contentIds);
                        } else {
                            return array();
                        }
                    }
                }
            }
        }
        if (is_array($classnameArr) && count($classnameArr)) {
            $q->andWhere($this->addInstanceFilters($q->getQuery(), "c", $classnameArr));
        }

        if (property_exists('BackBuilder\ClassContent\AClassContent', '_' . $selector['orderby'][0])) {
            $q->orderBy('c._' . $selector['orderby'][0], count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'asc');
        } else if (property_exists('BackBuilder\NestedNode\Page', '_' . $selector['orderby'][0])) {
            $q->orderBy('p._' . $selector['orderby'][0], count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'asc');
        } else {
            $q->leftJoin('c._indexation', 'isort')
                    ->andWhere('isort._field = :sort')
                    ->setParameter('sort', $selector['orderby'][0])
                    ->orderBy('isort._value', count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'asc');
        }
        $q->setFirstResult($start + $delta)->setMaxResults($limit ? $limit : (array_key_exists('limit', $selector) ? $selector['limit'] : 10) );

        return $multipage ? new Paginator($q) : $q->getQuery()->getResult();
    }

    /**
     * Returns a set of content by classname
     * @param array $classnameArr
     * @param array $orderInfos
     * @param array $limitInfos
     * @return array
     */
    public function findContentsByClassname($classnameArr = array(), $orderInfos = array(), $limitInfos = array())
    {
        $result = array();
        if (!is_array($classnameArr))
            return $result;
        $db = $this->_em->getConnection();

        $order = "";
        $start = (is_array($limitInfos) && array_key_exists("start", $limitInfos)) ? (int) $limitInfos["start"] : 0;
        $limit = (is_array($limitInfos) && array_key_exists("limit", $limitInfos)) ? (int) $limitInfos["limit"] : 0;
        $stmt = $db->executeQuery("SELECT * FROM `content` WHERE `classname` IN (?) order by modified desc limit ?,?", array($classnameArr, $start, $limit), array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, 1, 1)
        );

        /* $stmt->bindValue(1,$classnameArr,\Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
          $stmt->bindValue(2,$start,\PDO::PARAM_INT);
          $stmt->bindValue(3,$limit,\PDO::PARAM_INT); */
        $items = $stmt->fetchAll();
        if ($items) {
            foreach ($items as $item) {
                $content = $this->_em->find($item["classname"], $item["uid"]); //@fixme ->use ResultSetMapping
                if ($content)
                    $result[] = $content;
            }
        }
        return $result;
    }

    /**
     * Returns the hydrated content by its uid
     * @param string $uid
     * @return array|null the content if found
     */
    public function findContentByUid($uid)
    {
        $results = $this->findContentsByUids(array($uid));
        if (0 < count($results)) {
            return reset($results);
        }

        return null;
    }

    /**
     * Return the classnames from content uids
     * @param array $uids An array of content uids
     * @return array An array of the classnames
     */
    private function _getDistinctClassnamesFromUids(array $uids)
    {
        $classnames = array();

        try {
            if (0 < count($uids)) {
                // Data protection
                array_walk($uids, function(&$item) {
                            $item = addslashes($item);
                        });

                // Getting classnames for provided uids
                $classnames = $this->_em
                        ->getConnection()
                        ->createQueryBuilder()
                        ->select('classname')
                        ->from('content', 'c')
                        ->andWhere("uid IN ('" . implode("','", $uids) . "')")
                        ->execute()
                        ->fetchAll(\PDO::FETCH_COLUMN);
            }
        } catch (\Exception $e) {
            // Ignoring error
        }

        return array_unique($classnames);
    }

    /**
     * Returns the hydrated contents from their uids
     * @param array $uids
     * @return array An array of AClassContent
     */
    public function findContentsByUids(array $uids)
    {
        $result = array();
        try {
            if (0 < count($uids)) {
                // Getting classnames for provided uids
                $classnames = $this->_getDistinctClassnamesFromUids($uids);

                // Construct the DQL query
                $query = $this->createQueryBuilder('c');
                foreach (array_unique($classnames) as $classname) {
                    $query = $query->orWhere('c INSTANCE OF ' . $classname);
                }
                $query = $query->andWhere('c._uid IN (:uids)')
                        ->setParameter('uids', $uids);

                $result = $query->getQuery()->execute();
            }
        } catch (\Exception $e) {
            // Ignoring error
        }

        return $result;
    }

    /**
     * Add SQL filter to content search
     * @param \Doctrine\ORM\Query $query
     * @param string $name
     * @param array $classesArray
     * @return string
     */
    private function addInstanceFilters(Query $query, $name = null, $classesArray = array())
    {
        $dqlString = "";
        if (!is_string($name))
            return $dqlString;
        if (is_array($classesArray) && count($classesArray)) {
            $dqlString .= $query->getDql();
            $filters = array();
            foreach ($classesArray as $classname) {
                $filters[] = "c INSTANCE OF '" . $classname . "'";
            }
            $classFilter = implode(" OR ", $filters);
            return $classFilter;
        }
        return $qlString;
    }

    private function getPageMainContentSets($selectedNode, $online = false)
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult("BackBuilder\ClassContent\ContentSet", "c");

        $rsm->addFieldResult('c', 'uid', '_uid');
        $sql = "Select c.uid from content c LEFT JOIN content_has_subcontent sc ON c.uid = sc.content_uid";
        $sql .= " LEFT JOIN page as p ON p.contentset = sc.parent_uid";
        $sql .= " where p.uid = :selectedNodeUid";
        $sql .=" AND p.root_uid = :selectedPageRoot";
        $sql .= " AND p.leftnode >= :selectedPageLeftnode";
        $sql .= " AND p.rightnode <= :selectedPageRightnode";
       if($online){
           $sql .=" AND p.state IN(:selectedPageState)";
           }
        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameters(array(
            "selectedPageRoot" => $selectedNode->getRoot(),
            "selectedNodeUid" => $selectedNode->getUid(),
            "selectedPageLeftnode" => $selectedNode->getLeftnode(),
            "selectedPageRightnode" => $selectedNode->getRightnode()));
        if ($online) {  
            $query->setParameter("selectedPageState", array(Page::STATE_ONLINE, Page::STATE_ONLINE | Page::STATE_HIDDEN));
        }
        return $query->getResult();
    }

    /**
     * sql query example: select c.label, c.classname  FROM content c LEFT JOIN content_has_subcontent cs ON c.uid = cs.content_uid
     * where cs.parent_uid in (select cs.content_uid from page p LEFT JOIN content_has_subcontent cs ON p.contentset = cs.parent_uid
     *       where p.uid = '0007579e1888f8c2a7a0b74c615aa501'
     * );
     * 
     *
     * SELECT c.uid, c.label, c.classname
      FROM content_has_subcontent cs
      INNER JOIN content_has_subcontent cs1 ON cs1.parent_uid  = cs.content_uid
      left join content c on  cs1.content_uid = c.uid
      left join page p on p.contentset = cs.parent_uid
      Where p.uid="f70d5b294dcc4d8d5c7f57b8804f4de2"
     * 
     * select content where parent_uid
     * 
     * @param array $classnameArr
     * @param array $orderInfos
     * @param array $paging
     * @param array $cond
     * @return array
     */
    public function findContentsBySearch($classnameArr = array(), $orderInfos = array(), $paging = array(), $cond = array())
    {
        $qb = $this->_em->createQueryBuilder("c");
        $qb->select("c")->distinct()->from("BackBuilder\ClassContent\AClassContent", "c");
        if (array_key_exists("selectedpageField", $cond) && !is_null($cond["selectedpageField"]) && !empty($cond["selectedpageField"])) {
            $selectedNode = $this->_em->getRepository('BackBuilder\NestedNode\Page')->findOneBy(array('_uid' => $cond['selectedpageField']));

            /* tous les contentset de premier niveau 
             * SELECT *
              FROM `content` c
              LEFT JOIN content_has_subcontent sc ON c.uid = sc.content_uid
              LEFT JOIN page AS p ON p.contentset = sc.parent_uid
              where p.uid ="f70d5b294dcc4d8d5c7f57b8804f4de2"
             * 94b081b46015cb451b6aa14ea3807cc3
             * c470a895d6cb09d001b5fc5bb8613306
             */
            /* as content has no relation with page we are user a native Query */

            if ($selectedNode && !$selectedNode->isRoot()) {
                /* $qbSN = $this->createQueryBuilder('ct');
                  $subContentsQuery = $qbSN->leftJoin("ct._parentcontent", "sc")
                  // ->leftJoin("BackBuilder\NestedNode\Page", "p", "WITH", "p._contentset = sc")
                  //->leftJoin("ct._pages", "p")
                  ->andWhere('p._root = :selectedPageRoot')
                  ->andWhere('p._leftnode >= :selectedPageLeftnode')
                  ->andWhere('p._rightnode <= :selectedPageRightnode')
                  ->setParameters(array("selectedPageRoot" => $selectedNode->getRoot(),
                  "selectedPageLeftnode" => $selectedNode->getLeftnode(),
                  "selectedPageRightnode" => $selectedNode->getRightnode()))->getQuery(); */

                /* handle online content */
                $limitToOnline = ( array_key_exists("limitToOnline", $cond) && is_bool($cond["limitToOnline"]) ) ? $cond["limitToOnline"] : true;
                $subContents = $this->getPageMainContentSets($selectedNode, $limitToOnline);
                if(empty($subContents)){ return array(); } // should never happened
                $newQuery = $this->_em->createQueryBuilder("q");
                $newQuery->select("selectedContent")->from("BackBuilder\ClassContent\AClassContent", "selectedContent");
                $contents = $newQuery->leftJoin("selectedContent._parentcontent", "cs")
                                ->where("cs._uid IN (:scl)")
                                ->setParameter("scl", $subContents)->getQuery()->getResult(); // $subContentsQuery->getResult()
                /* filtre  parmi ces contents */
                $qb->where("c in (:sc) ")->setParameter("sc", $contents);
            }
        }

        if (true === array_key_exists('site_uid', $cond)) {
            $qb = $qb->andWhere('c._uid IN (SELECT i.content_uid FROM BackBuilder\ClassContent\Indexes\IdxSiteContent i WHERE i.site_uid = :site_uid)')->setParameter('site_uid', $cond['site_uid']);
        }

        /* @fixme handle keywords here using join */
        if (array_key_exists("keywords", $cond) && is_array($cond["keywords"]) && !empty($cond["keywords"])) {
            $contentIds = $this->_em->getRepository("BackBuilder\NestedNode\KeyWord")->getContentsIdByKeyWords($cond["keywords"]);
            if (is_array($contentIds) && !empty($contentIds)) {
                $qb->andWhere("c._uid in(:kwContent)")->setParameter("kwContent", $contentIds);
            } else {
                /* no result no need to go further */
                return array();
            }
        }

        /* filter by content id */
        if (array_key_exists("contentIds", $cond) && is_array($cond["contentIds"]) && !empty($cond["contentIds"])) {
            $qb->andWhere("c._uid in(:contentIds)")->setParameter("contentIds", $cond["contentIds"]);
        }

        /* limit to online */

        $limitToOnline = ( array_key_exists("limitToOnline", $cond) && is_bool($cond["limitToOnline"]) ) ? $cond["limitToOnline"] : true;
        if ($limitToOnline) {
            $qb->leftJoin('c._mainnode', 'mp');
            $qb->andWhere('mp._state IN (:states)')
                    ->setParameter('states', array(Page::STATE_ONLINE, Page::STATE_ONLINE | Page::STATE_HIDDEN));
        } /* else {
          $qb->andWhere('p._state < :statedeleted')
          ->setParameter('statedeleted', Page::STATE_DELETED);
          } */

        /* handle contentIds */
        if (is_array($classnameArr) && count($classnameArr)) {
            $qb->andWhere($this->addInstanceFilters($qb->getQuery(), "c", $classnameArr));
        }

        /* handle order info */
        if (!is_array($orderInfos) || (is_array($orderInfos) && (!array_key_exists("column", $orderInfos) || !array_key_exists("dir", $orderInfos)))) {
            $orderInfos = array("column" => "created", "dir" => "desc");
        }

        if (property_exists('BackBuilder\ClassContent\AClassContent', '_' . $orderInfos["column"])) {
            $qb->orderBy('c._' . $orderInfos["column"], array_key_exists("dir", $orderInfos) ? $orderInfos["dir"] : 'asc');
        } else {
            /* $qb->leftJoin('c._indexation', 'isort')
              ->andWhere('isort._field = :sort')
              ->setParameter('sort', $orderInfos["column"])
              ->orderBy('isort._value', array_key_exists("dir", $orderInfos) ? $orderInfos["dir"] : 'asc'); */
        }
        /* else try to use indexation */

        $searchField = (isset($cond['searchField'])) ? $cond['searchField'] : NULL;
        if (NULL != $searchField)
            $qb->andWhere($qb->expr()->like('c._label', $qb->expr()->literal('%' . $searchField . '%')));

        $afterPubdateField = (isset($cond['afterPubdateField'])) ? $cond['afterPubdateField'] : NULL;
        if (NULL != $afterPubdateField)
            $qb->andWhere('c._modified > :afterPubdateField')->setParameter('afterPubdateField', date('Y/m/d', $afterPubdateField));

        $beforePubdateField = (isset($cond['beforePubdateField'])) ? $cond['beforePubdateField'] : NULL;
        if (NULL != $beforePubdateField)
            $qb->andWhere('c._modified < :beforePubdateField')->setParameter('beforePubdateField', date('Y/m/d', $beforePubdateField));

        /* handle indexed fields */
        if (array_key_exists("indexedFields", $cond) && !empty($cond["indexedFields"])) {
            $this->handleIndexedFields($qb, $cond["indexedFields"]);
        }
        if (is_array($paging) && count($paging)) {
            if (array_key_exists("start", $paging) && array_key_exists("limit", $paging)) {
                $qb->setFirstResult($paging["start"])
                        ->setMaxResults($paging["limit"]);
                $result = new \Doctrine\ORM\Tools\Pagination\Paginator($qb);
            }
        } else {
            $result = $qb->getQuery()->getResult();
        }
        return $result;
    }

    /* handle custom search */

    private function handleIndexedFields($qb, $criteria)
    {
        $criteria = (is_array($criteria)) ? $criteria : array();
        /* join indexation */
        if (empty($criteria))
            return;
        foreach ($criteria as $criterion) {
            //ajouter test
            if (count($criterion) != 3)
                continue;
            $criterion = (object) $criterion;
            $alias = uniqid("i");
            $qb->leftJoin("c._indexation", $alias)
                    ->andWhere($alias . "._field = :field" . $alias)
                    ->andWhere($alias . "._value " . $criterion->op . " :value" . $alias)
                    ->setParameter("field" . $alias, $criterion->field)
                    ->setParameter("value" . $alias, $criterion->value);
        }
    }

    public function getLastByMainnode(Page $page, $classnames = array())
    {
        try {
            $q = $this->createQueryBuilder('c');

            foreach ($classnames as $classname)
                $q->orWhere('c INSTANCE OF ' . $classname);

            $q->andWhere('c._mainnode = :node')
                    ->orderby('c._modified', 'desc')
                    ->setMaxResults(1)
                    ->setParameters(array('node' => $page));

            $entity = $q->getQuery()->getSingleResult();
        } catch (\Exception $e) {
            $entity = NULL;
        }

        return $entity;
    }

    function countContentsByClassname($classname = array())
    {
        $result = 0;
        if (!is_array($classname))
            return $result;
        $db = $this->_em->getConnection();
        $stmt = $db->executeQuery("SELECT count(*) as total FROM `content` WHERE `classname` IN (?)", array($classname), array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY));

        $result = $stmt->fetchColumn();
        return $result;
    }

    function countContentsBySearch($classnameArr = array(), $cond = array())
    {

        $qb = $this->_em->createQueryBuilder("c");
        $qb->select($qb->expr()->count('c'))->from("BackBuilder\ClassContent\AClassContent", "c");

        if (array_key_exists("selectedpageField", $cond) && !is_null($cond["selectedpageField"]) && !empty($cond["selectedpageField"])) {
            $selectedNode = $this->_em->getRepository('BackBuilder\NestedNode\Page')->findOneBy(array('_uid' => $cond['selectedpageField']));
            if ($selectedNode && !$selectedNode->isRoot()) {
                /*$qbSN = $this->createQueryBuilder('ct');
                $subContentsQuery = $qbSN->leftJoin("ct._parentcontent", "sc")
                                //->leftJoin("\BackBuilder\NestedNode\Page", "p", "WITH", "sc = p._contentset")
                                ->leftJoin("ct._pages", "p")
                                ->andWhere('p._root = :selectedPageRoot')
                                ->andWhere('p._leftnode >= :selectedPageLeftnode')
                                ->andWhere('p._rightnode <= :selectedPageRightnode')
                                ->setParameters(array("selectedPageRoot" => $selectedNode->getRoot(),
                                    "selectedPageLeftnode" => $selectedNode->getLeftnode(),
                                    "selectedPageRightnode" => $selectedNode->getRightnode()))->getQuery();*/
                $limitToOnline = ( array_key_exists("limitToOnline", $cond) && is_bool($cond["limitToOnline"]) ) ? $cond["limitToOnline"] : true;
                $subContents = $this->getPageMainContentSets($selectedNode, $limitToOnline);
                if(empty($subContents)){ return array(); } // should never happened
                $newQuery = $this->_em->createQueryBuilder("q");
                $newQuery->select("selectedContent")->from("BackBuilder\ClassContent\AClassContent", "selectedContent");
                $contents = $newQuery->leftJoin("selectedContent._parentcontent", "cs")
                                ->where("cs._uid IN (:scl)")
                                ->setParameter("scl", $subContents)->getQuery()->getResult();

                /* filtre  parmi ces contents */
                $qb->where("c in (:sc) ")->setParameter("sc", $contents);
            }
        }


        /* contentType filter */
        if (is_array($classnameArr) && count($classnameArr)) {
            $qb->andWhere($this->addInstanceFilters($qb->getQuery(), "c", $classnameArr));
        }

        if (true === array_key_exists('site_uid', $cond)) {
            $qb = $qb->andWhere('c._uid IN (SELECT i.content_uid FROM BackBuilder\ClassContent\Indexes\IdxSiteContent i WHERE i.site_uid = :site_uid)')->setParameter('site_uid', $cond['site_uid']);
        }

        /* Keywords */
        if (array_key_exists("keywords", $cond) && is_array($cond["keywords"]) && !empty($cond["keywords"])) {
            $contentIds = $this->_em->getRepository("BackBuilder\NestedNode\KeyWord")->getContentsIdByKeyWords($cond["keywords"]);
            if (is_array($contentIds) && !empty($contentIds)) {
                $qb->andWhere("c._uid in(:kwContent)")->setParameter("kwContent", $contentIds);
            } else {
                $result = 0;
                return $result;
            }
        }
        /* limit to online */
        $limitToOnline = ( array_key_exists("limitToOnline", $cond) && is_bool($cond["limitToOnline"]) ) ? $cond["limitToOnline"] : true;
        if ($limitToOnline) {
            $qb->leftJoin('c._mainnode', 'mp');
            $qb->andWhere('mp._state IN (:states)')
                    ->setParameter('states', array(Page::STATE_ONLINE, Page::STATE_ONLINE | Page::STATE_HIDDEN));
        } /* else {
          $qb->andWhere('p._state < :statedeleted')
          ->setParameter('statedeleted', Page::STATE_DELETED);
          } */

        /* filter by content id */
        if (array_key_exists("contentIds", $cond) && is_array($cond["contentIds"]) && !empty($cond["contentIds"])) {
            $qb->andWhere("c._uid in(:contentIds)")->setParameter("contentIds", $cond["contentIds"]);
        }

        $searchField = (isset($cond['searchField'])) ? $cond['searchField'] : NULL;
        if (NULL != $searchField)
            $qb->andWhere($qb->expr()->like('c._label', $qb->expr()->literal('%' . $searchField . '%')));

        $afterPubdateField = (isset($cond['afterPubdateField'])) ? $cond['afterPubdateField'] : NULL;
        if (NULL != $afterPubdateField)
            $qb->andWhere('c._modified > :afterPubdateField')->setParameter('afterPubdateField', date('Y/m/d', $afterPubdateField));

        $beforePubdateField = (isset($cond['beforePubdateField'])) ? $cond['beforePubdateField'] : NULL;
        if (NULL != $beforePubdateField)
            $qb->andWhere('c._modified < :beforePubdateField')->setParameter('beforePubdateField', date('Y/m/d', $beforePubdateField));


        /* handle indexed fields */
        if (array_key_exists("indexedFields", $cond) && !empty($cond["indexedFields"])) {
            $this->handleIndexedFields($qb, $cond["indexedFields"]);
        }

        $result = $qb->getQuery()->getSingleResult();

        return reset($result);
    }

    /**
     * Do stuf on update by post of the content editing form
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param stdClass $value
     * @param \BackBuilder\ClassContent\AClassContent $parent
     * @return \BackBuilder\ClassContent\Element\file
     * @throws ClassContentException Occures on invalid content type provided
     */
    public function getValueFromPost(AClassContent $content, $value, AClassContent $parent = null)
    {
        /** enable form to edit more than one parameter */
        if (true === (property_exists($value, "parameters"))) {
            $parameters = (is_object($value->parameters)) ? array($value->parameters) : $value->parameters;
            if (is_array($parameters) && !empty($parameters)) {
                foreach ($parameters as $param) {
                    if (is_object($param)) {
                        if (true === property_exists($param, 'name') && true === property_exists($param, 'value')) {
                            $content->setParam($param->name, $param->value, 'scalar');
                        }
                    }
                }
            }
        }
        /* if (true === property_exists($value, 'parameters') && true === is_object($value->parameters)) {
          if (true === property_exists($value->parameters, 'name') && true === property_exists($value->parameters, 'value')) {
          $content->setParam($value->parameters->name, $value->parameters->value);
          }
          } */
        try {
            $content->value = $this->formatPost($content, $value);
            $content->value = html_entity_decode($content->value, ENT_COMPAT, 'UTF-8');
        } catch (\Exception $e) {
            // Nothing to do
        }

        return $content;
    }

    /**
     * Format a post (place here all other stuffs)
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param stdClass $value
     * @return string
     */
    public function formatPost(AClassContent $content, $value)
    {
        $val = $value->value;

        switch (get_class($content)) {
            case 'BackBuilder\ClassContent\Element\text':
                //nettoyage des images => div aloha
                $pattern = '{<div class=".*aloha-image.*".*>.?<(img[^\>]*).*>.*</div>}si';
                if (TRUE == preg_match($pattern, $val, $matches)) {
                    if (2 == count($matches)) {
                        $val = str_replace($matches[0], '<' . $matches[1] . '/>', $val);
                    }
                }
                break;
        }

        return $val;
    }

    /**
     * Do stuf removing content from the content editing form
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param type $value
     * @param \BackBuilder\ClassContent\AClassContent $parent
     * @return type
     * @throws ClassContentException
     */
    public function removeFromPost(AClassContent $content, $value = null, AClassContent $parent = null)
    {
        if (null !== $draft = $content->getDraft()) {
            $draft->setState(\BackBuilder\ClassContent\Revision::STATE_TO_DELETE);
        }

        return $content;
    }

    /**
     * Set the storage directories define by the BB5 application
     * @param \BackBuilder\BBApplication $application
     * @return \BackBuilder\ClassContent\Repository\Element\fileRepository
     */
    public function setDirectories(BBApplication $application = null)
    {
        return $this;
    }

    /**
     * Set the temporary directory
     * @param type $temporary_dir
     * @return \BackBuilder\ClassContent\Repository\Element\fileRepository
     */
    public function setTemporaryDir($temporary_dir = null)
    {
        return $this;
    }

    /**
     * Set the storage directory
     * @param type $storage_dir
     * @return \BackBuilder\ClassContent\Repository\Element\fileRepository
     */
    public function setStorageDir($storage_dir = null)
    {
        return $this;
    }

    /**
     * Set the media library directory
     * @param type $media_dir
     * @return \BackBuilder\ClassContent\Repository\Element\fileRepository
     */
    public function setMediaDir($media_dir = null)
    {
        return $this;
    }

    /**
     * Load content if need, the user's revision is also set
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param \BackBuilder\Security\Token\BBUserToken $token
     * @param boolean $checkoutOnMissing If true, checks out a new revision if none was found
     * @return \BackBuilder\ClassContent\AClassContent
     */
    public function load(AClassContent $content, \BackBuilder\Security\Token\BBUserToken $token = null, $checkoutOnMissing = false)
    {
        $revision = null;
        if (null !== $token) {
            $revision = $this->_em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $token, $checkoutOnMissing);
        }

        if (false === $content->isLoaded()) {
            $classname = \Symfony\Component\Security\Core\Util\ClassUtils::getRealClass($content);
            if (null !== $refresh = $this->_em->find($classname, $content->getUid())) {
                $content = $refresh;
            }
        }

        if (null !== $content) {
            $content->setDraft($revision);
        }

        return $content;
    }

}