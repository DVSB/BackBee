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

namespace BackBuilder\Util\Doctrine;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Utility class to deal with managed Doctrine entities
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @subpackage  Doctrine
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class ScheduledEntities
{

    /**
     * Returns an array of scheduled entities by classname for insertions
     * @param \Doctrine\ORM\EntityManager $em
     * @param string|array Classnames
     * @return array
     */
    public static function getScheduledEntityInsertionsByClassname(EntityManager $em, $classnames)
    {
        $entities = array();
        $classnames = (array) $classnames;

        foreach ($em->getUnitOfWork()->getScheduledEntityInsertions() as $entity) {
            if (true === in_array(ClassUtils::getRealClass($entity), $classnames)) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Returns an array of scheduled entities by classname for updates
     * @param \Doctrine\ORM\EntityManager $em
     * @param string|array Classnames
     * @return array
     */
    public static function getScheduledEntityUpdatesByClassname(EntityManager $em, $classnames)
    {
        $entities = array();
        $classnames = (array) $classnames;

        foreach ($em->getUnitOfWork()->getScheduledEntityUpdates() as $entity) {
            if (true === in_array(ClassUtils::getRealClass($entity), $classnames)) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Returns an array of scheduled entities by classname for deletions
     * @param \Doctrine\ORM\EntityManager $em
     * @param string|array Classnames
     * @return array
     */
    public static function getScheduledEntityDeletionsByClassname(EntityManager $em, $classnames)
    {
        $entities = array();
        $classnames = (array) $classnames;

        foreach ($em->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            if (true === in_array(ClassUtils::getRealClass($entity), $classnames)) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Returns an array of scheduled entities by classname for insertions, updates or deletions
     * @param \Doctrine\ORM\EntityManager $em
     * @param string|array Classnames
     * @return array
     */
    public static function getScheduledEntityByClassname(EntityManager $em, $classnames)
    {
        return array_merge(
                self::getScheduledEntityInsertionsByClassname($em, $classnames), 
                self::getScheduledEntityUpdatesByClassname($em, $classnames), 
                self::getScheduledEntityDeletionsByClassname($em, $classnames)
        );
    }

    /**
     * Returns an array of AClassContent scheduled for insertions
     * @param \Doctrine\ORM\EntityManager $em
     * @param boolean $with_revision Includes AClassContent which has scheduled revision
     * @return array
     */
    public static function getScheduledAClassContentInsertions(EntityManager $em, $with_revision = false)
    {
        $entities = array();
        foreach ($em->getUnitOfWork()->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof \BackBuilder\ClassContent\AClassContent) {
                $entities[] = $entity;
            } elseif (true === $with_revision && $entity instanceof \BackBuilder\ClassContent\Revision) {
                $entities[] = $entity->getContent();
            }
        }

        return $entities;
    }

    /**
     * Returns an array of AClassContent scheduled for updates
     * @param \Doctrine\ORM\EntityManager $em
     * @param boolean $with_revision Includes AClassContent which has scheduled revision
     * @return array
     */
    public static function getScheduledAClassContentUpdates(EntityManager $em, $with_revision = false)
    {
        $entities = array();
        foreach ($em->getUnitOfWork()->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof \BackBuilder\ClassContent\AClassContent) {
                $entities[] = $entity;
            } elseif (true === $with_revision && $entity instanceof \BackBuilder\ClassContent\Revision) {
                $entities[] = $entity->getContent();
            }
        }

        return $entities;
    }

    /**
     * Returns an array of AClassContent scheduled for deletions
     * @param \Doctrine\ORM\EntityManager $em
     * @return array
     */
    public static function getSchedulesAClassContentDeletions(EntityManager $em)
    {
        $entities = array();
        foreach ($em->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof \BackBuilder\ClassContent\AClassContent) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Returns an array of AClassContent scheduled for insertions or updates
     * @param \Doctrine\ORM\EntityManager $em
     * @param boolean $with_revision Includes AClassContent which has scheduled revision
     * @return array
     */
    public static function getScheduledAClassContentNotForDeletions(EntityManager $em, $with_revision = false)
    {
        return array_merge(self::getScheduledAClassContentInsertions($em, $with_revision), self::getScheduledAClassContentUpdates($em, $with_revision));
    }

    /**
     * Returns TRUE if a entity of $classname is scheduled for insertion or update
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $classname
     * @return boolean
     */
    public static function hasScheduledEntitiesNotForDeletions(EntityManager $em, $classname)
    {
        $entities = array_merge($em->getUnitOfWork()->getScheduledEntityInsertions(), $em->getUnitOfWork()->getScheduledEntityUpdates());

        foreach ($entities as $entity) {
            if (is_a($entity, $classname)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns TRUE is a \BackBuilder\NestedNode\Page is scheduled for insertion or update
     * @param \Doctrine\ORM\EntityManager $em
     * @return boolean
     */
    public static function hasScheduledPageNotForDeletions(EntityManager $em)
    {
        return self::hasScheduledEntitiesNotForDeletions($em, '\BackBuilder\NestedNode\Page');
    }

}
