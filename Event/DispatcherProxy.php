<?php
namespace BackBuilder\Event;

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

use BackBuilder\DependencyInjection\ContainerInterface;
use BackBuilder\DependencyInjection\Dumper\DumpableServiceProxyInterface;
use BackBuilder\Event\Dispatcher;

/**
 * This interface must be implemented if you want to use a proxy class instead of your service real class
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class DispatcherProxy extends Dispatcher implements DumpableServiceProxyInterface
{
    /**
     * define if dispatcher is already restored by container or not
     *
     * @var boolean
     */
    private $_is_restored;

    /**
     * DispatcherProxy's constructor
     */
    public function __construct()
    {
        $this->_is_restored = false;
    }


    /**
     * Restore current service to the dump's state
     *
     * @param  array $dump the dump provided by DumpableServiceInterface::dump() from where we can
     *                     restore current service
     */
    public function restore(ContainerInterface $container, array $dump)
    {
        $this->_application = $container->get('bbapp');

        foreach ($dump['listeners'] as $name => $callable) {
            $this->addListener($name, $callable);
        }

        $this->_is_restored = true;
    }


    /**
     * @return boolean true if current service is already restored, otherwise false
     */
    public function isRestored()
    {
        return $this->_is_restored;
    }
}