<?php
namespace BackBuilder\DependencyInjection\Loader;

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

/**
 *
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class PhpArrayLoader
{
    /**
     * [$container description]
     * @var BackBuilder\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * [__construct description]
     * @param ContainerInterface $container [description]
     */
    public function __construct(ContainerInterface &$container)
    {
        $this->container = &$container;
    }

    /**
     * [load description]
     * @param  [type] $filepath [description]
     * @return [type]           [description]
     */
    public function load($filepath)
    {
        $dump = null;

        if (true === is_readable($filepath)) {
            $dump = unserialize(file_get_contents($filepath));
        }

        if (null === $dump) {
            throw new \Exception($filepath . ' is not readable.');
        }

        if (false === is_array($dump)) {
            throw new \Exception('Content getted from ' . $filepath . ' is not a valid format (array expected).');
        }

        if (false === array_key_exists('parameters', $dump) || false === array_key_exists('services', $dump)) {
            throw new \Exception();
        }

        $this->container = new \BackBuilder\DependencyInjection\Loader\ContainerProxy($dump);
    }
}