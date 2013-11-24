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

namespace BackBuilder\NestedNode\Repository;

use BackBuilder\NestedNode\Page;

/**
 * Keyword repository
 * 
 * @category    BackBuilder
 * @package     BackBuilder/NestedNode
 * @subpackage  Repository
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class KeyWordRepository extends NestedNodeRepository
{

    public function getLikeKeyWords($cond)
    {
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

    public function getRoot()
    {
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

    public function getKeywordTreeAsArray($node = null)
    {
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

    public function getContentsIdByKeyWords($keywords, $limitToOnline = true)
    {
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

