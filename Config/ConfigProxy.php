<?php
namespace BackBuilder\Config;

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

use BackBuilder\Config\Config;
use BackBuilder\DependencyInjection\ContainerInterface;
use BackBuilder\DependencyInjection\Dumper\DumpableServiceProxyInterface;

/**
 * This interface must be implemented if you want to use a proxy class instead of your service real class
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ConfigProxy extends Config implements DumpableServiceProxyInterface
{
    /**
     * represents if current service has been already restored or not
     *
     * @var boolean
     */
    protected $is_restored;

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
        $this->_basedir = $dump['basedir'];
        $this->_raw_parameters = $dump['raw_parameters'];
        $this->_environment = $dump['environment'];
        $this->_debug = $dump['debug'];
        $this->_yml_names_to_ignore = $dump['yml_names_to_ignore'];

        if (true === $dump['has_container']) {
            $this->setContainer($container);
        }

        if (true === $dump['has_cache']) {
            $this->setCache($container->get('cache.bootstrap'));
        }

        $this->is_restored = true;
    }


    /**
     * @return boolean true if current service is already restored, otherwise false
     */
    public function isRestored()
    {
        return $this->is_restored;
    }
}