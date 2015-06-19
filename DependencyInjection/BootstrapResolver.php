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

namespace BackBee\DependencyInjection;

use Symfony\Component\Yaml\Yaml;
use BackBee\BBApplication;
use BackBee\DependencyInjection\Exception\BootstrapFileNotFoundException;
use BackBee\Util\Resolver\BootstrapDirectory;

/**
 * This resolver allow us to find the right bootstrap.yml file with a base directory, a context
 * and a environment. Resolver will follow this path, from the most specific to the most global:.
 *
 *     BASE_DIRECTORY
 *         |_ Config
 *             |_ ENVIRONMENT
 *                 |_ bootstrap.yml (3)
 *             |_ bootstrap.yml (4)
 *         |_ CONTEXT
 *             |_ Config
 *                 |_ ENVIRONMENT
 *                     |_ bootstrap.yml (1)
 *                 |_ bootstrap.yml (2)
 *
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class BootstrapResolver
{
    const BOOTSTRAP_FILENAME = 'bootstrap.yml';

    /**
     * base directory from where we define in which directory to look into.
     *
     * @var string
     */
    private $baseDir;

    /**
     * application's context.
     *
     * @var string
     */
    private $context;

    /**
     * application's environment.
     *
     * @var string
     */
    private $environment;

    /**
     * BootstrapResolver's constructor.
     *
     * @param string $baseDir     base directory from where we define in which directory to look into
     * @param string $context     application's context
     * @param string $environment application's environment
     */
    public function __construct($baseDir, $context, $environment)
    {
        $this->baseDir = $baseDir;
        $this->context = null === $context ? BBApplication::DEFAULT_CONTEXT : $context;
        $this->environment = null === $environment ? BBApplication::DEFAULT_ENVIRONMENT : $environment;
    }

    /**
     * Returns an array which contains every parameters in bootstrap.yml file (use application context
     * and environment to find the right one).
     *
     * @return array of parameters coming from the bootstrap.yml file
     *
     * @throws BootstrapFileNotFoundException if bootstrap.yml is not found or not readable
     */
    public function getBootstrapParameters()
    {
        $bootstrapFilepath = null;

        $directories = BootstrapDirectory::getDirectories($this->baseDir, $this->context, $this->environment);
        foreach ($directories as $directory) {
            $bootstrapFilepath = $directory.DIRECTORY_SEPARATOR.self::BOOTSTRAP_FILENAME;
            if (is_file($bootstrapFilepath) && is_readable($bootstrapFilepath)) {
                break;
            }

            $bootstrapFilepath = null;
        }

        if (null === $bootstrapFilepath) {
            throw new BootstrapFileNotFoundException($directories);
        }

        $parameters = Yaml::parse(file_get_contents($bootstrapFilepath));
        $parameters['bootstrap_filepath'] = $bootstrapFilepath;

        return $parameters;
    }
}
