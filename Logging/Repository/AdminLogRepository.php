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

namespace BackBuilder\Logging\Repository;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use BackBuilder\Logging\AdminLog;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Logging
 * @subpackage  Repository
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class AdminLogRepository extends EntityRepository
{

    public function log($owner, $classname, $method, $entity)
    {
        $log = new AdminLog();
        $log->setOwner($owner)
                ->setAction($method)
                ->setController($classname);
        if ($entity !== null) {
            $log->setEntity($entity);
        }
        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush($log);
    }

    public function countOtherUserInTheSamePage($controller, $action, $entity)
    {
        $date = new \DateTime('@' . strtotime('-30 minutes'));
        $from = 'SELECT owner, entity FROM admin_log ' .
                'WHERE created_at > "' . $date->format('Y-m-d H:i:s') . '" ' .
                'AND controller = "\\\\' . str_replace('\\', '\\\\', $controller) . '" ' .
                'AND action = "' . $action . '" ' .
                'AND entity = "' . str_replace('\\', '\\\\', (string) ObjectIdentity::fromDomainObject($entity)) . '" ' .
                'ORDER BY created_at DESC';

        $sql = 'SELECT owner, entity FROM (' . $from . ') AS orderer_log GROUP BY owner';
        $result = $this->getEntityManager()->getConnection()->executeQuery($sql);

        $verif = $this->_getActualAdminEdition();
        $return = $result->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($return as $key => $result) {
            if (
                    array_key_exists($result['owner'], $verif) &&
                    $verif[$result['owner']] != $result['entity']
            ) {
                unset($return[$key]);
            }
        }
        return count($return);
    }

    public function getLastEditedContent()
    {
        $query = $this->createQueryBuilder("al")
                ->where("al._action=:action")->setParameter("action", "subscriberEdit")
                ->andWhere("al._entity IS NOT NULL")
                ->setMaxResults(1)
                ->orderBy("al._created_at", "DESC");
        $query_result = $query->getQuery()->getResult();
        $result = reset($query_result);
        return $result;
    }

    private function _getActualAdminEdition()
    {
        $date = new \DateTime('@' . strtotime('-30 minutes'));
        $from = 'SELECT owner, entity FROM admin_log WHERE created_at > "' . $date->format('Y-m-d H:i:s') . '" ORDER BY created_at DESC';
        $sql = 'SELECT owner, entity FROM (' . $from . ') AS orderer_log GROUP BY owner';
        $result = $this->getEntityManager()->getConnection()->executeQuery($sql);

        $verif = array();
        foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $result) {
            $verif[$result['owner']] = $result['entity'];
        }
        return $verif;
    }

}