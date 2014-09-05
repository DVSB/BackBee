<?php
namespace BackBuilder\Rest\Mapping;

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
    /**
     * @var array
     */
    public $queryParams = array();

    /**
     * @var array
     */
    public $requestParams = array();

    /**
     * @var integer
     */
    public $default_start;

    /**
     * @var integer
     */
    public $default_count;

    /**
     * @var integer
     */
    public $max_count;

    /**
     * @var integer
     */
    public $min_count;

    /**
     * serialize current object
     *
     * @return string
     */
    public function serialize()
    {
        return \serialize([
            $this->class,
            $this->name,
            $this->queryParams,
            $this->requestParams,
            $this->default_count,
            $this->max_count,
            $this->min_count
        ]);
    }

    /**
     * unserialize
     *
     * @param  string $str
     */
    public function unserialize($str)
    {
        list(
            $this->class,
            $this->name,
            $this->queryParams,
            $this->requestParams,
            $this->default_count,
            $this->max_count,
            $this->min_count
        ) = \unserialize($str);

        $this->reflection = new \ReflectionMethod($this->class, $this->name);
        $this->reflection->setAccessible(true);
    }
}
