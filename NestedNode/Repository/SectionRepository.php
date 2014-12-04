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
     * Returns an array of uid of the children of $node_uid
     * @param string $node_uid  The node uid to look for children
     * @return array
     */
    public function getNativelyNodeChildren($node_uid)
    {
        $query = $this->createQueryBuilder('s')
                ->select('s._uid')
                ->where('s._parent = :node')
                ->addOrderBy('s._leftnode', 'asc')
                ->addOrderBy('s._modified', 'desc');

        $result = $this->getEntityManager()
                ->getConnection()
                ->executeQuery($query->getQuery()->getSQL(), array($node_uid), array(\Doctrine\DBAL\Types\Type::STRING))
                ->fetchAll();

        return Arrays::array_column($result, 'uid0');
    }

}
