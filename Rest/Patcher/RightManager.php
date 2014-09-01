<?php
namespace BackBuilder\Rest\Patcher;

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

use BackBuilder\Rest\Patcher\PatcherInterface;

/**
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class RightManager
{
    /**
     * [$rights description]
     *
     * @var [type]
     */
    private $rights;

    /**
     * [__construct description]
     *
     * @param array $rights [description]
     */
    public function __construct(array $rights)
    {
        $this->buildRights($rights);
    }

    /**
     * [authorized description]
     *
     * @param  object $entity    [description]
     * @param  [type] $attribute [description]
     * @param  [type] $operation [description]
     *
     * @return [type]            [description]
     */
    public function authorized($entity, $attribute, $operation)
    {
        $authorized = true;
        $classname = get_class($entity);
        if (false === array_key_exists($classname, $this->rights)) {
            $authorized = false;
        }

        $attribute = str_replace('/', '', $attribute);
        if (true === $authorized && false === array_key_exists($attribute, $this->rights[$classname])) {
            $authorized = false;
        }

        if (true === $authorized && false === in_array($operation, $this->rights[$classname][$attribute])) {
            $authorized = false;
        }

        return $authorized;
    }

    /**
     * [buildRights description]
     *
     * @param  array  $rights [description]
     */
    private function buildRights(array $rights)
    {
        foreach ($rights as $classname => $permissions) {
            $this->rights[$classname] = array();
            foreach ($permissions['attributes'] as $attr) {
                $this->rights[$classname][$attr] = (array) $permissions['valid_operations'];
            }
        }
    }
}
