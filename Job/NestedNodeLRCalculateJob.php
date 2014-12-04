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

namespace BackBuilder\Job;

use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Calculates the left/right values for a nest node
 *
 * @category    BackBuilder
 * @package     BackBuilder\Job
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class NestedNodeLRCalculateJob extends AJob
{
    /**
     * The entity manager to be used
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Sets the entity manager to use
     * @param  \Doctrine\ORM\EntityManager                   $em
     * @return \BackBuilder\Job\NestedNodeDetachCalculateJob
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

        $first = $args['first'];
        $delta = $args['delta'];

        $classname = ClassUtils::getRealClass($node);

        $this->em->getRepository($classname)
            ->createQueryBuilder('n')
            ->set('n._leftnode', 'n._leftnode + :delta')
            ->andWhere('n._root = :root')
            ->andWhere('n._leftnode >= :leftnode')
            ->andWhere('n._uid <> :uid')
            ->setParameters(array(
                'delta' => $delta,
                'root' => $node->getRoot(),
                'leftnode' => $first,
                'uid' => $node->getUid(), ))
            ->update()
            ->getQuery()
            ->execute()
        ;

        $this->em->getRepository($classname)
            ->createQueryBuilder('n')
            ->set('n._rightnode', 'n._rightnode + :delta')
            ->andWhere('n._root = :root')
            ->andWhere('n._rightnode >= :rightnode')
            ->andWhere('n._uid != :uid')
            ->setParameters(array(
                'delta' => $delta,
                'root' => $node->getRoot(),
                'rightnode' => $first,
                'uid' => $node->getUid(), ))
            ->update()
            ->getQuery()
            ->execute()
        ;
    }
}
