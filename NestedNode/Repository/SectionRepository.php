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

use BackBuilder\Site\Site;
use BackBuilder\Util\Arrays;
use Doctrine\ORM\NoResultException;

/**
 * Section repository
 *
 * @category    BackBuilder
 * @package     BackBuilder/NestedNode
 * @subpackage  Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class SectionRepository extends NestedNodeRepository
{

    /**
     * Returns the root section for $site tree
     * @param \BackBuilder\Site\Site $site   the site to test
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getRoot(Site $site)
    {
        try {
            $q = $this->createQueryBuilder('s')
                    ->andWhere('s._site = :site')
                    ->andWhere('s._parent is null')
                    ->setMaxResults(1)
                    ->setParameters(array(
                'site' => $site
            ));
            return $q->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Updates nodes information of a tree
     * @param string $node_uid  The starting point in the tree
     * @param int $leftnode     Optional, the first value of left node
     * @param int $level        Optional, the first value of level
     * @return \StdClass
     */
    public function updateTreeNatively($node_uid, $leftnode = 1, $level = 0)
    {
        $node = new \StdClass();
        $node->uid = $node_uid;
        $node->leftnode = $leftnode;
        $node->rightnode = $leftnode + 1;
        $node->level = $level;

        foreach ($children = $this->getNativelyNodeChildren($node_uid) as $child_uid) {
            $child = $this->updateTreeNatively($child_uid, $leftnode + 1, $level + 1);
            $node->rightnode = $child->rightnode + 1;
            $leftnode = $child->rightnode;
        }

        $this->updateSectionNodes($node->uid, $node->leftnode, $node->rightnode, $node->level)
                ->updatePageLevel($node->uid, $node->level);

        return $node;
    }

    /**
     * Returns an array of uid of the children of $node_uid
     * @param string $node_uid  The node uid to look for children
     * @return array
     */
    public function getNativelyNodeChildren($node_uid)
    {
        $query = $this->createQueryBuilder('s')
                ->select('s._uid', 's._leftnode')
                ->where('s._parent = :node')
                ->addOrderBy('s._leftnode', 'asc')
                ->addOrderBy('s._modified', 'desc')
                ->getQuery()
                ->getSQL();

        $result = $this->getEntityManager()
                ->getConnection()
                ->executeQuery($query, array($node_uid), array(\Doctrine\DBAL\Types\Type::STRING))
                ->fetchAll();

        return Arrays::array_column($result, 'uid0');
    }

    /**
     * Updates nodes information for Section $section_uid
     * @param string $section_uid
     * @param int $leftnode
     * @param int $rightnode
     * @param int $level
     * @return \BackBuilder\NestedNode\Repository\SectionRepository
     * @codeCoverageIgnore
     */
    private function updateSectionNodes($section_uid, $leftnode, $rightnode, $level)
    {
        $this->createQueryBuilder('s')
                ->update()
                ->set('s._leftnode', $leftnode)
                ->set('s._rightnode', $rightnode)
                ->set('s._level', $level)
                ->where('s._uid = :uid')
                ->setParameter('uid', $section_uid)
                ->getQuery()
                ->execute();

        return $this;
    }

    /**
     * Updates level of page attach to section $section_uid
     * @param string $section_uid
     * @param int $level
     * @return \BackBuilder\NestedNode\Repository\SectionRepository
     * @codeCoverageIgnore
     */
    private function updatePageLevel($section_uid, $level)
    {
        $page_repo = $this->getEntityManager()
                ->getRepository('BackBuilder\NestedNode\Page');

        $page_repo->createQueryBuilder('p')
                ->update()
                ->set('p._level', $level)
                ->where('p._uid = :uid')
                ->setParameter('uid', $section_uid)
                ->getQuery()
                ->execute();

        $page_repo->createQueryBuilder('p')
                ->update()
                ->set('p._level', $level + 1)
                ->where('p._section = :uid')
                ->andWhere('p._uid <> :uid')
                ->setParameter('uid', $section_uid)
                ->getQuery()
                ->execute();

        return $this;
    }

}
