<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\DependencyInjection\Tests;

use BackBee\DependencyInjection\ContainerInterface;
use BackBee\DependencyInjection\Dumper\DumpableServiceProxyInterface;

/**
 * RandomServiceProxy is the proxy class for RandomService when container will restore RandomService.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class RandomServiceProxy extends RandomService implements DumpableServiceProxyInterface
{
    /**
     * ConfigProxy's constructor.
     *
     * @param array $dump
     */
    public function __construct()
    {
        $this->is_restored = false;
    }

    /**
     * Restore current service to the dump's state.
     *
     * @param array $dump the dump provided by DumpableServiceInterface::dump() from where we can
     *                    restore current service
     */
    public function restore(ContainerInterface $container, array $dump)
    {
        $this->setSize($dump['size']);

        $this->is_restored = true;
    }
}
