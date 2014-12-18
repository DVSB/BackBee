<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Job;

use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Calculates the left/right values for a nest node move around one of its siblings
 *
 * @category    BackBee
 * @package     BackBee\Job
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class NestedNodeMoveSiblingsJob extends AJob
{
    /**
     * The entity manager to be used
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Sets the entity manager to use
     * @param  \Doctrine\ORM\EntityManager                   $em
     * @return \BackBee\Job\NestedNodeDetachCalculateJob
     */
    public function setEntityManager($em)
    {
        $this->em = $em;

        return $this;
    }

    /**
     * Run the job
     * @param  mixed                     $args
     * @throws \InvalidArgumentException
     */
    public function run($args)
    {
        if (isset($args['node'])) {
            $node = $args['node'];
        } elseif (isset($args['nodeClass']) && isset($args['nodeId'])) {
            $node = $this->em->getRepository($args['nodeClass'])->find($args['nodeId']);
            if (null === $node) {
                throw new \InvalidArgumentException(sprintf('Unknown node %s(%s)', $args['nodeClass'], $args['nodeId']));
            }
        } else {
            throw new \InvalidArgumentException('Nested Node is missing');
        }

        $currentRoot = $node->getRoot();
        $max_right = $currentRoot->getRightnode();

        $previous_left = $args['previous_left'];
        $previous_right = $previous_left + $node->getWeight() - 1;

        $classname = ClassUtils::getRealClass($node);

        // Update nodes of the moved tree at the end
        $this->em->getRepository($classname)
                ->createQueryBuilder('n')
                ->set('n._leftnode', 'n._leftnode + :delta')
                ->set('n._rightnode', 'n._rightnode + :delta')
                ->andWhere('n._root = :root')
                ->andWhere('n._leftnode >= :left')
                ->andWhere('n._rightnode <= :right')
                ->andWhere('n._uid <> :uid')
                ->setParameters(array(
                    'delta' => $max_right,
                    'root' => $currentRoot,
                    'left' => $previous_left,
                    'right' => $previous_right,
                    'uid' => $node->getUid(), ))
                ->update()
                ->getQuery()
                ->execute();

        if ($args['previous_left'] > $args['new_left']) {
            // Move nodes between new left and previous left
            $this->em->getRepository($classname)
                    ->createQueryBuilder('n')
                    ->set('n._leftnode', 'n._leftnode + :delta')
                    ->set('n._rightnode', 'n._rightnode + :delta')
                    ->andWhere('n._root = :root')
                    ->andWhere('n._leftnode >= :newleft')
                    ->andWhere('n._rightnode < :previousleft')
                    ->andWhere('n._uid <> :uid')
                    ->setParameters(array(
                        'delta' => $node->getWeight(),
                        'root' => $currentRoot,
                        'newleft' => $args['new_left'],
                        'previousleft' => $args['previous_left'],
                        'uid' => $node->getUid(), ))
                    ->update()
                    ->getQuery()
                    ->execute();
        } else {
            // Move nodes between previous right and new right
            $this->em->getRepository($classname)
                    ->createQueryBuilder('n')
                    ->set('n._leftnode', 'n._leftnode + :delta')
                    ->set('n._rightnode', 'n._rightnode + :delta')
                    ->andWhere('n._root = :root')
                    ->andWhere('n._leftnode > :previousright')
                    ->andWhere('n._rightnode <= :newright')
                    ->andWhere('n._uid <> :uid')
                    ->setParameters(array(
                        'delta' => -1 * $node->getWeight(),
                        'root' => $currentRoot,
                        'previousright' => $args['previous_left'],
                        'newright' => $args['new_left'] + $node->getWeight() - 1,
                        'uid' => $node->getUid(), ))
                    ->update()
                    ->getQuery()
                    ->execute();
        }

        // Finally move the moved node
        $this->em->getRepository($classname)
                ->createQueryBuilder('n')
                ->set('n._leftnode', 'n._leftnode + :delta')
                ->set('n._rightnode', 'n._rightnode + :delta')
                ->andWhere('n._root = :root')
                ->andWhere('n._leftnode > :left')
                ->andWhere('n._uid <> :uid')
                ->setParameters(array(
                    'delta' => $args['new_left'] - $args['previous_left'] - $max_right,
                    'root' => $currentRoot,
                    'left' => $max_right,
                    'uid' => $node->getUid(), ))
                ->update()
                ->getQuery()
                ->execute();
    }
}
