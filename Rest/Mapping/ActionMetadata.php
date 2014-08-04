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

namespace BackBuilder\Rest\Mapping;

use Metadata\MethodMetadata;

/**
 * Stores controller action metadata
 *
 * @Annotation
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class ActionMetadata extends MethodMetadata
{
    public $class;
    public $name;
    public $queryParams = array();
    public $requestParams = array();
    
    public $paginationStartName;
    public $paginationLimitName;
    public $paginationLimitDefault;
    public $paginationLimitMax;
    public $paginationLimitMin;
    
    public $reflection;
 
    public function __construct($class, $name)
    {
        $this->class = $class;
        $this->name = $name;

        $this->reflection = new \ReflectionMethod($class, $name);
        $this->reflection->setAccessible(true);
    }
    
    public function serialize()
    {
        return \serialize([
            $this->class, 
            $this->name, 
            $this->queryParams, 
            $this->requestParams,
            $this->paginationStartName,
            $this->paginationLimitName,
            $this->paginationLimitDefault,
            $this->paginationLimitMax,
            $this->paginationLimitMin
        ]);
    }

    public function unserialize($str)
    {
        list(
            $this->class, 
            $this->name, 
            $this->queryParams, 
            $this->requestParams,
            $this->paginationStartName,
            $this->paginationLimitName,
            $this->paginationLimitDefault,
            $this->paginationLimitMax,
            $this->paginationLimitMin
        ) = \unserialize($str);

        $this->reflection = new \ReflectionMethod($this->class, $this->name);
        $this->reflection->setAccessible(true);
    }
 
    
}
