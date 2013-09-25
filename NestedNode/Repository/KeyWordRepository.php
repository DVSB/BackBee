<?php

namespace BackBuilder\NestedNode\Repository;

use BackBuilder\NestedNode\KeyWord;
use BackBuilder\NestedNode\ANestedNode;
use BackBuilder\NestedNode\Page;

class KeyWordRepository extends NestedNodeRepository {

    public function getLikeKeyWords($cond) {
        try {
            $q = $this->createQueryBuilder('k')->andWhere('k._keyWord like :key')->orderBy('k._keyWord', 'ASC')->setMaxResults(10)
                    ->setParameters(array('key' => $cond . '%'))
                    ->getQuery();
            return $q->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getRoot() {
        try {
            $q = $this->createQueryBuilder('k')
                    ->andWhere('k._parent is NULL')
                    ->getQuery();
            return $q->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getKeywordTreeAsArray($node = null) {
        $node = (is_null($node)) ? $this->getRoot() : $node;
        $nodeInfos = new \stdClass();
        $nodeInfos->uid = $node->getUid();
        $nodeInfos->level = $node->getLevel();
        $nodeInfos->keyword = $node->getKeyword();
        $nodeInfos->children = array();
        $children = $this->getDescendants($node, 1);
        if (is_array($children)) {
            foreach ($children as $child) {
                $nodeInfos->children[] = $this->getKeywordTreeAsArray($child);
            }
        }
        return $nodeInfos;
    }

    public function getContentsIdByKeyWords($keywords, $limitToOnline = true) {
        try {
            if (isset($keywords) && !empty($keywords)) {

                $keywords = (is_array($keywords)) ? $keywords : array($keywords);
                $db = $this->_em->getConnection();
                $queryString = "SELECT content.uid 
                    FROM
                        keywords_contents 
                    LEFT JOIN 
                        content on (content.uid = keywords_contents.content_uid)
                    LEFT JOIN 
                        page on (content.node_uid = page.uid)
                    WHERE
                        keywords_contents.keyword_uid IN (?)";

                if ($limitToOnline) {
                    $queryString .=" AND page.state IN (?)";
                    $pageStates = array(Page::STATE_ONLINE, Page::STATE_ONLINE | Page::STATE_HIDDEN);
                    $secondParam = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
                } else {
                    $pageStates = Page::STATE_HIDDEN;
                    $queryString .=" AND page.state < (?)";
                    $secondParam = 1;
                }
                $stmt = $db->executeQuery($queryString, array($keywords, $pageStates), array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, $secondParam));
                $result = array();
                while ($contendId = $stmt->fetchColumn()) {
                    $result[] = $contendId;
                }
                return $result;
            }
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

}

