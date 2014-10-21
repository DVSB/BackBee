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

namespace BackBuilder\DependencyInjection\Tests;

use BackBuilder\DependencyInjection\ContainerInterface;
use BackBuilder\DependencyInjection\Dumper\DumpableServiceProxyInterface;
use BackBuilder\DependencyInjection\Tests\RandomService;

/**
 * RandomServiceProxy is the proxy class for RandomService when container will restore RandomService
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class RandomServiceProxy extends RandomService implements DumpableServiceProxyInterface
{
    /**
     * ConfigProxy's constructor
     *
     * @param array $dump
     */
    public function __construct()
    {
        $this->is_restored = false;
    }

    /**
     * Restore current service to the dump's state
     *
     * @param  array $dump the dump provided by DumpableServiceInterface::dump() from where we can
     *                     restore current service
     */
    public function restore(ContainerInterface $container, array $dump)
    {
        $this->setSize($dump['size']);

        $this->is_restored = true;
    }
}
