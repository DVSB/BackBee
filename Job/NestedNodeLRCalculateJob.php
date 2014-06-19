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
    public function run($args)
    {
        $em = $this->container->get('em');
        
        if(isset($args['nodeClass']) && isset($args['nodeId'])) {
            $node = $args['node'];
        } elseif(isset($args['nodeClass']) && isset($args['nodeId'])) {
            $node = $em->getRepository($args['nodeClass'])->find($args['nodeId']);
        } else {
            throw new \InvalidArgumentException('Nested Node is missing');
        }
        
        $first = $args['first'];
        $delta = $args['delta'];
        
        $q = $em->getRepository($node)->createQueryBuilder('n')
                ->set('n._leftnode', 'n._leftnode + :delta')
                ->andWhere('n._root = :root')
                ->andWhere('n._leftnode >= :leftnode')
                ->setParameters(array(
                    'delta' => $delta,
                    'root' => $node->getRoot(),
                    'leftnode' => $first))
                ->update()
                ->getQuery()
                ->execute();

        $q = $em->getRepository($node)->createQueryBuilder('n')
                ->set('n._rightnode', 'n._rightnode + :delta')
                ->andWhere('n._root = :root')
                ->andWhere('n._rightnode >= :rightnode')
                ->setParameters(array(
                    'delta' => $delta,
                    'root' => $node->getRoot(),
                    'rightnode' => $first))
                ->update()
                ->getQuery()
                ->execute();
    }

}