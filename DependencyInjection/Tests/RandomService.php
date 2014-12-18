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

namespace BackBee\DependencyInjection\Tests;

use BackBee\DependencyInjection\Dumper\DumpableServiceInterface;

/**
 * This class is used by ContainerProxyTest for its tests
 *
 * @category    BackBee
 * @package     BackBee\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class RandomService implements DumpableServiceInterface
{
    const DEFAULT_SIZE = 100;
    const RANDOM_SERVICE_PROXY_CLASSNAME = 'BackBee\DependencyInjection\Tests\RandomServiceProxy';

    /**
     * @var int
     */
    private $size;

    /**
     * @var string
     */
    private $class_proxy;

    /**
     * represents if current service has been already restored or not
     *
     * @var boolean
     */
    protected $is_restored;

    /**
     * RandomService
     *
     * @param int $size the new size's value
     */
    public function __construct($size = self::DEFAULT_SIZE)
    {
        $this->size = $size;
        $this->class_proxy = self::RANDOM_SERVICE_PROXY_CLASSNAME;
    }

    /**
     * Getter for size attribute
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Setter for size attribute
     *
     * @param int $size the new size value
     *
     * @return RandomService current instance (this)
     */
    public function setSize($size)
    {
        $this->size = (int) $size;

        return $this;
    }

    public function setClassProxy($class_proxy)
    {
        $this->class_proxy = $class_proxy;

        return $this;
    }

    /**
     * @see BackBee\DependencyInjection\Dumper\DumpableServiceInterface::getClassProxy
     */
    public function getClassProxy()
    {
        return $this->class_proxy;
    }

    /**
     * @see BackBee\DependencyInjection\Dumper\DumpableServiceInterface::dump
     */
    public function dump(array $options = array())
    {
        return array('size' => $this->size);
    }

    /**
     * @return boolean true if current service is already restored, otherwise false
     */
    public function isRestored()
    {
        return $this->is_restored;
    }
}
