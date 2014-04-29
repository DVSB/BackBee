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

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\Query\ResultSetMapping;

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
    public function findEntityById($classname, $id)
    {
        
        $sql = 'SELECT * FROM BackBuilder\\Bundle\\Registry WHERE type = :type AND ((key = "id" AND value = :id) OR (scope = :id))';
        $query = $this->_em->createNativeQuery($sql, $this->getResultSetMapping());
        $query->setParameter(':type', $classname)
              ->setParameter(':id', $id);

        return $this->buildEntity($classname, $query->getQuery()->getResult());
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

    private function getResultSetMapping()
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult('BackBuilder\Bundle\Registry', 'br');
        $rsm->addFieldResult('br', 'id', 'id');
        $rsm->addFieldResult('br', 'type', 'type');
        $rsm->addMetaResult('br', 'key', 'key');
        $rsm->addMetaResult('br', 'value', 'value');
        $rsm->addMetaResult('br', 'scope', 'scope');

        return $rsm;
    }

    private function countEntities($classname, $total)
    {
        $property_number = count((new $classname())->getProperties());

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

    private function buildEntity($classname, $content)
    {
        return (new Builder())->setRegistries($content, $classname)->getEntity();
    }
}
