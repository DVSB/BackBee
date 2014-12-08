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
use Doctrine\ORM\Query\ResultSetMapping;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Repository extends EntityRepository
{
    private $last_inserted_id;

    /**
     * Saves the registry entry in DB, persist it if need
     * @param \BackBuilder\Bundle\Registry $registry
     * @return \BackBuilder\Bundle\Registry
     */
    public function save(\BackBuilder\Bundle\Registry $registry)
    {
        if (false === $this->getEntityManager()->contains($registry)) {
            $this->getEntityManager()->persist($registry);
        }

        $this->getEntityManager()->flush($registry);

        return $registry;
    }

    /**
     * Removes the registry entry from DB
     * @param \BackBuilder\Bundle\Registry $registry
     * @return \BackBuilder\Bundle\Registry
     */
    public function remove(\BackBuilder\Bundle\Registry $registry)
    {
        if(\Doctrine\ORM\UnitOfWork::STATE_NEW !== $this->getEntityManager()->getUnitOfWork()->getEntityState($registry)) {
            $this->getEntityManager()->remove($registry);
            $this->getEntityManager()->flush($registry);
        }



        return $registry;
    }

    /**
     * Removes the registry entry from DB
     * @param \BackBuilder\Bundle\Registry $registry
     * @return \BackBuilder\Bundle\Registry
     */
    public function removeEntity($entity)
    {
        $registries = $this->findRegistriesEntityById(get_class($entity), $entity->getObjectIdentifier());

        foreach ($registries as $registry) {
            $this->remove($registry);
        }
    }

    public function findRegistryEntityByIdAndScope($id, $scope)
    {
        $result = $this->_em->getConnection()->executeQuery(sprintf(
            'SELECT `key`, `value`, `scope` FROM registry WHERE `key` = "%s" AND `scope` = "%s"',
             $id,
             $scope
        ))->fetch();

        $registry = null;
        if (false !== $result) {
            $registry = new \BackBuilder\Bundle\Registry();
            $registry->setKey($result['key']);
            $registry->setValue($result['value']);
            $registry->setScope($result['scope']);
        }

        return $registry;
    }

    /**
     * Find the entity by hes id
     *
     * @param $classname
     **/
    public function findRegistriesEntityById($identifier, $id)
    {
        $sql = 'SELECT * FROM registry AS r WHERE (r.type = :identifier OR r.scope = :identifier) AND ((r.key = "identifier" AND r.value = :id) OR (r.scope = :id))';
        $query = $this->_em->createNativeQuery($sql, $this->getResultSetMapping());
        $query->setParameters(array('identifier' => $identifier,
            'id' => $id
            )
        );

        return $query->getResult();
    }

    /**
     * Find the entity by hes id
     *
     * @param $classname
     **/
    public function findEntityById($identifier, $id)
    {
        return $this->buildEntity($identifier, $this->findRegistriesEntityById($identifier, $id));
    }

    /**
     * Find the entity by hes id
     *
     * @param $classname
     **/
    public function findEntity($id)
    {
        return $this->findEntityById($this->getEntityName(), $id);
    }

    public function count($descriminator = null)
    {
        if (is_null($identifier)) {
            $identifier = $this->getEntityName();
        }

        $sql = 'SELECT count(*) as count FROM registry AS br WHERE br.%s = "' . $descriminator . '"';

        if (class_exists($descriminator) && (new Builder())->isRegistryEntity(new $descriminator())) {
            $count = $this->countEntities($descriminator, $this->executeSql(sprintf($sql, 'type')));
        } else {
            $count = $this->executeSql(sprintf($sql, 'scope'));
        }

        return $count;
    }

    public function findAllEntities($identifier = null)
    {
        if (is_null($identifier)) {
            $identifier = $this->getEntityName();
        }
        $sql = 'SELECT * FROM registry AS r WHERE r.key = "identifier" AND (r.type = :identifier OR r.scope = :identifier) ORDER BY r.id';
        $query = $this->_em->createNativeQuery($sql, $this->getResultSetMapping());
        $query->setParameter('identifier', $identifier);

        $entities = array();
        foreach ($query->getResult() as $key => $value) {
            $entities[$key] = $this->findEntityById($identifier, $value->getValue());
        }

        return $entities;
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
        $property_number = count((new $classname())->getObjectProperties());

        if ($property_number != 0) {
            $count = $total / ($property_number + 1);
        } else {
            $count = $total;
        }

        return $count;
    }

    public function persist($entity)
    {
        if ($entity instanceof DomainObjectInterface && $entity instanceof IRegistryEntity && null === $entity->getObjectIdentifier()) {
            if (!$this->last_inserted_id) {
                $this->last_inserted_id = $this->getLastInsertedId();
            }
            $entity->setObjectIdentifier($this->last_inserted_id++);
        }

        foreach ((new Builder())->setEntity($entity)->getRegistries() as $registry) {
            $this->_em->persist($registry);
            $this->_em->flush($registry);
        }
    }

    private function getLastInsertedId()
    {
        return $this->_em->getConnection()->lastInsertId('registry');
    }

    private function buildEntity($classname, $contents)
    {
        return (new Builder())->setRegistries($contents, $classname)->getEntity();
    }
}
