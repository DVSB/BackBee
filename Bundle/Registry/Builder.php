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

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class EntityBuilder
{
    private $isRegistryEntity = false;
    private $entity;
    private $contents;

    public function __construct()
    {
        $this->contents = $contents;

        if ($this->isRegistryEntity($classname)) {
            $this->entity = new {$classname}();
            $this->buildEntityClass();
        } else {
            $this->entity = new \stdClass();
            $this->buildStdClass();
        }
    }

    public function getEntity()
    {
        return $this->entity;
    }

    private function buildEntityClass()
    {
        foreach ($this->contents as $content) {
            # code...
        }
    }

    public function isRegistryEntity($classname = null)
    {
        if (!is_null($classname)) {
            $this->isRegistryEntity = (
                class_exists($classname) &&
                $classname instanceof IRegistryEntity
            );
        }

        return $this->isRegistryEntity;
    }
}