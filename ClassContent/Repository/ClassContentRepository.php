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

use BackBuilder\BBApplication;
use BackBuilder\ClassContent\AClassContent;
use BackBuilder\ClassContent\ContentSet;
use BackBuilder\NestedNode\Page;
use BackBuilder\Security\Token\BBUserToken;
use BackBuilder\Util\Doctrine\SettablePaginator;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

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
     * @param string content uid
     * @return array
     */
    public function getParentContentUidByUid($content_uid)
    {
        $q = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('c.parent_uid')
                ->from('content_has_subcontent', 'c')
                ->andWhere('c.content_uid = :uid')
                ->setParameter('uid', $content_uid);

        return $q->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get all content uids owning the provided content
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return array
     */
    public function getParentContentUid(AClassContent $content)
    {
        return $this->getParentContentUidByUid($content->getUid());
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
                "cpageLeftnode"    => $page->getLeftnode(),
                "cpageRightnode"   => $page->getRightnode()
            ))
            ->getQuery()->getResult()
        ;

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
     *
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
        $query = 'SELECT c.uid FROM content c';
        $join = array();
        $where = array();
        $orderby = array();
        $limit = $limit ? $limit : (array_key_exists('limit', $selector) ? $selector['limit'] : 10);
        $offset = $start + $delta;

        if (true === is_array($classnameArr) && 0 < count($classnameArr)) {
            foreach ($classnameArr as $classname) {
                // ensure Doctrine already known these classname
                class_exists($classname);
            }
            $where[] = str_replace('\\', '\\\\', 'c.classname IN ("' . implode('","', $classnameArr) . '")');
        }

        if (true === array_key_exists('content_uid', $selector)) {
            $uids = (array) $selector['content_uid'];
            if (false === empty($uids)) {
                $where[] = 'c.uid IN ("' . implode('","', $uids) . '")';
            }
        }

        if (true === array_key_exists('criteria', $selector)) {
            $criteria = (array) $selector['criteria'];
            foreach ($criteria as $field => $crit) {
                $crit = (array) $crit;
                if (1 == count($crit)) {
                    $crit[1] = '=';
                }

                $alias = uniqid('i' . rand());
                $join[] = 'LEFT JOIN indexation ' . $alias . ' ON c.uid  = ' . $alias . '.content_uid';
                $where[] = $alias . '.field = "' . $field . '" AND ' . $alias . '.value ' . $crit[1] . ' "' . $crit[0] . '"';
            }
        }

        if (true === array_key_exists('indexedcriteria', $selector) &&
                true === is_array($selector['indexedcriteria'])) {
            foreach ($selector['indexedcriteria'] as $field => $values) {
                $values = array_filter((array) $values);
                if (0 < count($values)) {
                    $alias = md5($field);
                    $join[] = 'LEFT JOIN indexation ' . $alias . ' ON c.uid  = ' . $alias . '.content_uid';
                    $where[] = $alias . '.field = "' . $field . '" AND ' . $alias . '.value IN ("' . implode('","', $values) . '")';
                }
            }
        }

        if (true === array_key_exists("keywordsselector", $selector)) {
            $keywordInfos = $selector["keywordsselector"];
            if (true === is_array($keywordInfos)) {
                if (true === array_key_exists("selected", $keywordInfos)) {
                    $selectedKeywords = $keywordInfos["selected"];
                    if (true === is_array($selectedKeywords)) {
                        $selectedKeywords = array_filter($selectedKeywords);
                        if (false === empty($selectedKeywords)) {
                            $contentIds = $this->_em->getRepository("BackBuilder\NestedNode\KeyWord")->getContentsIdByKeyWords($selectedKeywords, false);
                            if (true === is_array($contentIds) && false === empty($contentIds)) {
                                $where[] = 'c.uid IN ("' . implode('","', $contentIds) . '")';
                            } else {
                                return array();
                            }
                        }
                    }
                }
            }
        }

        if (false === array_key_exists('orderby', $selector)) {
            $selector['orderby'] = array('created', 'desc');
        } else {
            $selector['orderby'] = (array) $selector['orderby'];
        }

        $has_page_joined = false;
        if (array_key_exists('parentnode', $selector) && true === is_array($selector['parentnode'])) {
            $parentnode = array_filter($selector['parentnode']);
            if (false === empty($parentnode)) {
                $nodes = $this->_em->getRepository('BackBuilder\NestedNode\Page')->findBy(array('_uid' => $parentnode));
                if (count($nodes) != 0) {
                    $has_page_joined = true;
                    $query = 'SELECT c.uid FROM page p USE INDEX(IDX_SELECT_PAGE) LEFT JOIN content c ON c.node_uid=p.uid';
                    $pageSelection = array();
                    foreach ($nodes as $node) {
                        if (true === $recursive) {
                            $pageSelection[] = '(p.root_uid="' . $node->getRoot()->getUid() . '" AND p.leftnode BETWEEN ' . $node->getLeftnode() . ' AND ' . $node->getRightnode() . ')';
                        } else {
                            $pageSelection[] = '(p.parent_uid="' . $node->getUid() . '")';
                        }
                    }

                    if (count($pageSelection) != 0) {
                        $where[] = '(' . implode(' OR ', $pageSelection) . ')';
                    }

                    if (true === $limitToOnline) {
                        $where[] = 'p.state IN (1, 3)';
                        $where[] = '(p.publishing IS NULL OR p.publishing <= "' . date('Y-m-d H:i:00', time()) . '")';
                        $where[] = '(p.archiving IS NULL OR p.archiving >"' . date('Y-m-d H:i:00', time()) . '")';
                    } else {
                        $where[] = 'p.state < 4';
                    }

                    if (true === property_exists('BackBuilder\NestedNode\Page', '_' . $selector['orderby'][0])) {
                        $orderby[] = 'p.' . $selector['orderby'][0] . ' ' . (count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
                    } else if (true === property_exists('BackBuilder\ClassContent\AClassContent', '_' . $selector['orderby'][0])) {
                        $orderby[] = 'c.' . $selector['orderby'][0] . ' ' . (count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
                    } else {
                        $join[] = 'LEFT JOIN indexation isort ON c.uid  = isort.content_uid';
                        $where[] = 'isort.field = "' . $selector['orderby'][0] . '"';
                        $orderby[] = 'isort.value' . ' ' . (count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
                    }
                }
            }
        }

        if (0 === count($orderby)) {
            if (true === property_exists('BackBuilder\ClassContent\AClassContent', '_' . $selector['orderby'][0])) {
                $orderby[] = 'c.' . $selector['orderby'][0] . ' ' . (count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
            } else {
                $join[] = 'LEFT JOIN indexation isort ON c.uid  = isort.content_uid';
                $where[] = 'isort.field = "' . $selector['orderby'][0] . '"';
                $orderby[] = 'isort.value' . ' ' . (count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
            }
        }

        if (0 < count($join)) {
            $query .= ' ' . implode(' ', $join);
        }

        if (0 < count($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        //Optimize multipage query
        if (true === $multipage) {
            $query = str_replace('SELECT c.uid', 'SELECT SQL_CALC_FOUND_ROWS c.uid', $query);
            $query = str_replace('USE INDEX(IDX_SELECT_PAGE)', ' ', $query);
        }

        $uids = $this->getEntityManager()
                ->getConnection()
                ->executeQuery(str_replace('JOIN content c', 'JOIN opt_content_modified c', $query) . ' ORDER BY ' . implode(', ', $orderby) . ' LIMIT ' . $limit . ' OFFSET ' . $offset)
                ->fetchAll(\PDO::FETCH_COLUMN);

        if (count($uids) < $limit) {
            $uids = $this->getEntityManager()
                    ->getConnection()
                    ->executeQuery($query . ' ORDER BY ' . implode(', ', $orderby) . ' LIMIT ' . $limit . ' OFFSET ' . $offset)
                    ->fetchAll(\PDO::FETCH_COLUMN);
        }

        $q = $this->createQueryBuilder('c')
                ->select()
                ->where('c._uid IN (:uids)')
                ->setParameter('uids', $uids);

        if (true === $has_page_joined && true === property_exists('BackBuilder\NestedNode\Page', '_' . $selector['orderby'][0])) {
            $q->join('c._mainnode', 'p')
                    ->orderBy('p._' . $selector['orderby'][0], count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
        } else if (true === property_exists('BackBuilder\ClassContent\AClassContent', '_' . $selector['orderby'][0])) {
            $q->orderBy('c._' . $selector['orderby'][0], count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
        } else {
            $q->leftJoin('c._indexation', 'isort')
                    ->andWhere('isort._field = :sort')
                    ->setParameter('sort', $selector['orderby'][0])
                    ->orderBy('isort._value', count($selector['orderby']) > 1 ? $selector['orderby'][1] : 'desc');
        }

        if (true === $multipage) {

            $num_results = $this->getEntityManager()->getConnection()->executeQuery('SELECT FOUND_ROWS()')->fetch(\PDO::FETCH_COLUMN);
            $result = $q->getQuery()->getResult();

            $q->setFirstResult($offset)
                    ->setMaxResults($limit);

            $paginator = new SettablePaginator($q);
            $paginator
                ->setCount($num_results)
                ->setResult($result)
            ;

            return $paginator;
        }

        $result = $q->getQuery()->getResult();
        return $result;
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
        if ($online) {
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
     * @param array $classnames
     * @param array $orderInfos
     * @param array $paging
     * @param array $cond
     * @return array
     */
    public function findContentsBySearch($classnames = array(), $orderInfos = array(), $paging = array(), $cond = array())
    {
        $qb = new ClassContentQueryBuilder($this->_em);

        $this->addContentBySearchFilters($qb, $classnames, $orderInfos, $cond);
        if (is_array($paging) && count($paging)) {
            if (array_key_exists('start', $paging) && array_key_exists('limit', $paging)) {
                $result = $qb->paginate($paging['start'], $paging['limit']);
            } else {
                $result = $qb->getQuery();
            }
        } else {
            $result = $qb->getQuery();
        }
        return $result;

    }

    public function countContentsBySearch($classnames = array(), $cond = array())
    {
        $qb = new ClassContentQueryBuilder($this->_em, $this->_em->getExpressionBuilder()->count('cc'));
        $this->addContentBySearchFilters($qb, $classnames, array(), $cond);
        try {
            $result = $qb->getQuery()->getSingleResult();
        } catch (\Exception $e) {
            return 0;
        }

        return reset($result);
    }

    private function addContentBySearchFilters(ClassContentQueryBuilder $qb, $classnames, $orderInfos, $cond)
    {
        if (array_key_exists('selectedpageField', $cond) && !is_null($cond['selectedpageField']) && !empty($cond['selectedpageField'])) {
        $selectedNode = $this->_em->getRepository('BackBuilder\NestedNode\Page')->findOneBy(array('_uid' => $cond['selectedpageField']));
            $qb->addPageFilter($selectedNode);
        }

        if (is_array($classnames) && count($classnames)) {
            $qb->addClassFilter($classnames);
        }

        if (true === array_key_exists('site_uid', $cond)) {
            $qb->addSiteFilter($cond['site_uid']);
        }

        /* @fixme handle keywords here using join */
        if (array_key_exists('keywords', $cond) && is_array($cond['keywords']) && !empty($cond['keywords'])) {
            $qb->addKeywordsFilter($cond['keywords']);
        }

        /* limit to online */
        $limitToOnline = ( array_key_exists('only_online', $cond) && is_bool($cond['only_online']) ) ? $cond['only_online'] : true;
        if ($limitToOnline) {
            $qb->limitToOnline();
        }

        /* filter by content id */
        if (array_key_exists('contentIds', $cond) && is_array($cond['contentIds']) && !empty($cond['contentIds'])) {
            $qb->addUidsFilter((array)$cond['contentIds']);
        }

        /* handle order info */
        if (is_array($orderInfos) && array_key_exists('column', $orderInfos)) {
            $orderInfos['column'] = ('_' === $orderInfos['column'][0] ? '' : '_') . $orderInfos['column'];
            if (property_exists('BackBuilder\ClassContent\AClassContent', $orderInfos['column'])) {
                $qb->orderBy('cc.' . $orderInfos['column'], array_key_exists('direction', $orderInfos) ? $orderInfos['direction'] : 'ASC');
            } else {
                $qb->orderByIndex($orderInfos['column'], array_key_exists('direction', $orderInfos) ? $orderInfos['direction'] : 'ASC');
            }
        }

        /* else try to use indexation */
        $searchField = (isset($cond['searchField'])) ? $cond['searchField'] : null;
        if (null != $searchField) {
            $qb->andWhere($qb->expr()->like('cc._label', $qb->expr()->literal('%' . $searchField . '%')));
        }

        $afterPubdateField = (isset($cond['afterPubdateField'])) ? $cond['afterPubdateField'] : null;
        if (null != $afterPubdateField) {
            $qb->andWhere('cc._modified > :afterPubdateField')->setParameter('afterPubdateField', date('Y/m/d', $afterPubdateField));
        }

        $beforePubdateField = (isset($cond['beforePubdateField'])) ? $cond['beforePubdateField'] : null;
        if (null != $beforePubdateField) {
            $qb->andWhere('cc._modified < :beforePubdateField')->setParameter('beforePubdateField', date('Y/m/d', $beforePubdateField));
        }

        /* handle indexed fields */
        if (array_key_exists('indexedFields', $cond) && !empty($cond['indexedFields'])) {
            $this->handleIndexedFields($qb, $cond['indexedFields']);
        }
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
            $alias = uniqid("i" . rand());
            $qb->leftJoin("cc._indexation", $alias)
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

            foreach ($classnames as $classname) {
                $q->orWhere('c INSTANCE OF ' . $classname);
            }

            $q->andWhere('c._mainnode = :node')
                ->orderby('c._modified', 'desc')
                ->setMaxResults(1)
                ->setParameters(array('node' => $page))
            ;

            $entity = $q->getQuery()->getSingleResult();
        } catch (\Exception $e) {
            $entity = null;
        }

        return $entity;
    }

    public function countContentsByClassname($classname = array())
    {
        $result = 0;
        if (!is_array($classname))
            return $result;
        $db = $this->_em->getConnection();
        $stmt = $db->executeQuery("SELECT count(*) as total FROM `content` WHERE `classname` IN (?)", array($classname), array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY));

        $result = $stmt->fetchColumn();
        return $result;
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

    /**
     * Returns the unordered children uids for $content
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return array
     */
    public function getUnorderedChildrenUids(AClassContent $content)
    {
        return $this->getEntityManager()
                        ->getConnection()
                        ->executeQuery('SELECT content_uid FROM content_has_subcontent WHERE parent_uid=?', array($content->getUid()))
                        ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return Collection<Page>
     */
    public function findPagesByContent($content)
    {
        /* Remoter � la racine pour trouver sur quelle page se trouve le contenu */
        $rootContents = array();
        $this->getRootContentParents($content, $rootContents);
        $qb = $this->_em->createQueryBuilder("p");
        $qb->select("p")->from("BackBuilder\NestedNode\Page", "p")
                ->andWhere('p._contentset IN (:contentset)')
                ->setParameter('contentset', $rootContents);
        $result = $qb->getQuery()->getResult();
        return $result;
    }

    private function getRootContentParents($content, &$rootContainer)
    {
        $contentParents = $content->getParentContent();
        /* if it has no parents --> is a root element */
        if ($contentParents->isEmpty()) {
            $rootContainer[] = $content;
        } else {
            foreach ($contentParents as $content) {
                $this->getRootContentParents($content, $rootContainer);
            }
        }
        return $rootContainer;
    }

    /**
     * Returns an uid if parent with this classname found, false otherwise
     * @param string $child_uid
     * @param string $class_name
     * @return string|false
     */
    public function getParentByClassName($child_uid, $class_name)
    {
        $q = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('j.parent_uid, c.classname')
                ->from('content_has_subcontent', 'j')
                ->from('content', 'c')
                ->andWhere('c.uid = j.parent_uid')
                ->andWhere('j.content_uid = :uid')
                ->setParameter('uid', $child_uid);

        $result = $q->execute()->fetch();
        if (false !== $result) {
            if ($result['classname'] == $class_name) {
                return $this->_em->find($class_name, $result['parent_uid']);
            } else {
                $result = $this->getParentByClassName($result['parent_uid'], $class_name);
            }
        } else {
            return null;
        }

        return $result;
    }

    /**
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return
     */
    public function deleteContent(AClassContent $content, $mainContent = true)
    {
        $parents = $content->getParentContent();
        $media = $this->_em->getRepository('BackBuilder\NestedNode\Media')->findOneBy(array(
            '_content' => $content->getUid()
        ));

        if (($parents->count() <= 1 && null === $media) || true === $mainContent) {
            foreach ($content->getData() as $element) {
                if ($element instanceof AClassContent) {
                    $this->deleteContent($element, false);
                }
            }

            if ($content instanceof ContentSet) {
                $content->clear();
            }

            foreach ($parents as $parent) {
                $parent->unsetSubContent($content);
            }

            $this->_em->getConnection()->executeQuery(
                'DELETE FROM indexation WHERE owner_uid = "' . $content->getUid() . '";'
            )->execute();
            $this->_em->getConnection()->executeQuery(
                'DELETE FROM revision WHERE content_uid = "' . $content->getUid() . '";'
            )->execute();
            $this->_em->remove($content);
        }
    }

    public function getClassnames(array $content_uids)
    {
        $content_uids = array_filter($content_uids);
        if (0 === count($content_uids)) {
            return array();
        }

        $sql = 'SELECT DISTINCT c.classname FROM content c WHERE c.uid IN ("' . implode('","', $content_uids) . '")';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($sql)
            ->fetchAll(\PDO::FETCH_COLUMN)
        ;
    }

}
