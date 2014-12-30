<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\DependencyInjection\Util;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use BackBee\DependencyInjection\Container;
use BackBee\DependencyInjection\ContainerBuilder;

/**
 * Allows to easily load services into container from yml or xml file
 *
 * @category    BackBee
 * @package     BackBee\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ServiceLoader
{
    /**
     * Load services from yaml file into your container
     *
     * @param Container    $container        the container we want to load services into
     * @param string|array $dir              directory (or directories) in where we can find services files
     * @param string|null  $service_filename define the service's filename we want to load,
     *                                       default: ContainerBuilder::SERVICE_FILENAME
     */
    public static function loadServicesFromYamlFile(Container $container, $dir, $service_filename = null)
    {
        if (null === $service_filename) {
            $service_filename = ContainerBuilder::SERVICE_FILENAME;
        }

        (new YamlFileLoader($container, new FileLocator((array) $dir)))->load($service_filename.'.yml');
    }

    /**
     * Load services from xml file into your container
     *
     * @param Container    $container        the container we want to load services into
     * @param string|array $dir              directory (or directories) in where we can find services files
     * @param string|null  $service_filename define the service's filename we want to load,
     *                                       default: ContainerBuilder::SERVICE_FILENAME
     */
    public static function loadServicesFromXmlFile(Container $container, $dir, $service_filename = null)
    {
        if (null === $service_filename) {
            $service_filename = ContainerBuilder::SERVICE_FILENAME;
        }

        (new XmlFileLoader($container, new FileLocator((array) $dir)))->load($service_filename.'.xml');
    }
}
