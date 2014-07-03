<?php
namespace BackBuilder\DependencyInjection\Util;

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

use BackBuilder\DependencyInjection\Container;
use BackBuilder\DependencyInjection\ContainerBuilder;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ServiceLoader
{
    /**
     * [loadServicesFromYamlFile description]
     * @param  Container $container [description]
     * @param  [type]    $dir       [description]
     * @return [type]               [description]
     */
    public static function loadServicesFromYamlFile(Container $container, $dir)
    {
        (new YamlFileLoader($container, new FileLocator((array) $dir)))->load(
            ContainerBuilder::SERVICE_FILENAME . '.yml'
        );
    }

    /**
     * [loadServicesFromXmlFile description]
     * @param  Container $container [description]
     * @param  [type]    $dir       [description]
     * @return [type]               [description]
     */
    public static function loadServicesFromXmlFile(Container $container, $dir)
    {
        (new XmlFileLoader($container, new FileLocator((array) $dir)))->load(
            ContainerBuilder::SERVICE_FILENAME . '.xml'
        );
    }
}
