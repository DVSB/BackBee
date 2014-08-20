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
use BackBuilder\DependencyInjection\Exception\ContainerDumpInvalidFormatException;
use BackBuilder\DependencyInjection\Exception\InvalidContainerDumpFilePathException;
use BackBuilder\DependencyInjection\Exception\MissingParametersContainerDumpException;

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
     * The container to hydrate
     *
     * @var BackBuilder\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * PhpArrayLoader's constructor
     *
     * @param ContainerInterface $container the container to hydrate with container dump
     */
    public function __construct(ContainerInterface &$container)
    {
        $this->container = &$container;
    }

    /**
     * Try to restore container with the dump located at $filepath path
     *
     * @param  string $filepath [description]
     *
     * @throws
     */
    public function load($filepath)
    {
        $dump = null;

        if (true === is_readable($filepath)) {
            $dump = unserialize(file_get_contents($filepath));
        }

        if (null === $dump) {
            throw new InvalidContainerDumpFilePathException($filepath);
        }

        if (false === is_array($dump)) {
            throw new ContainerDumpInvalidFormatException($filepath, gettype($dump));
        }

        if (
            false === array_key_exists('parameters', $dump)
            || false === array_key_exists('services', $dump)
            || false === array_key_exists('aliases', $dump)
            || false === array_key_exists('services_dump', $dump)
            || false === array_key_exists('is_compiled', $dump)
        ) {
            throw new MissingParametersContainerDumpException();
        }

        $this->container = new \BackBuilder\DependencyInjection\Loader\ContainerProxy($dump);
    }
}