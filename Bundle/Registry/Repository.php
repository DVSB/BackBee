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

namespace BackBuilder\Bundle\Registry;

use Doctrine\ORM\EntityRepository;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Repository extends EntityRepository
{
    /**
     * Find 
     *
     **/
    public function find($classname, $id)
    {
        $query = $this->createQueryBuilder('br');
        $query->where('type = :classname')
              ->setParameter(':classname', $classname)
              ->andWhere('objectid = :id')
              ->setParameter(':id', $id);

        return $this->buildEntity($classname, $id, $query->getQuery()->getResult());
    }

    public function count($descriminator)
    {
        $query = $this->createQueryBuilder('br');
        $query->select($qb->expr()->count('br'))
              ->setParameter(':descriminator', $descriminator);

        if ((new Builder())->isRegistryEntity($descriminator)) {
            $query->where('type = :descriminator');
            $count = $qb->getQuery()->getSingleResult();
            $count = $this->countEntities($descriminator, $count);
        } else {
            $query->where('scope = :descriminator');
            $count = $qb->getQuery()->getSingleResult();
            $count = reset($count);
        }

        return $count;
    }

    public function countEntities($classname, $total)
    {
        $property_number = count((new {$classname}())->getDatas());

        if ($property_number != 0) {
            $count = $total / $property_number;
        } else {
            $count = $total;
        }

        return $count;
    }

    public function save($entity)
    {
        foreach ((new Builder())->setEntity($entity)->getContents() as $registry) {
            $this->_em->persist($registry);
        }
        $this->_em->flush();
    }

    private function buildEntity($classname, $id, $content)
    {
        return (new Builder())->setContents($classname, $content)->getEntity();
    }
}
