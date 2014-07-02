<?php
namespace BackBuilder\DependencyInjection;

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

use BackBuilder\BBApplication;
use BackBuilder\DependencyInjection\Exception\BootstrapFileNotFoundException;

use Symfony\Component\Yaml\Yaml;

/**
 * This resolver allow us to find the right bootstrap.yml file with a base directory, a context
 * and a environment. Resolver will follow this path, from the most specific to the most global:
 *
 *     BASE_DIRECTORY
 *         |_ Config
 *             |_ ENVIRONMENT
 *                 |_ bootstrap.yml (3)
 *             |_ bootstrap.yml (4)
 *         |_ CONTEXT
 *             |_ ENVIRONMENT
 *                 |_ bootstrap.yml (1)
 *             |_ bootstrap.yml (2)
 *
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class BootstrapResolver
{
    const BOOTSTRAP_FILENAME = 'bootstrap.yml';

    /**
     * base directory from where we define in which directory to look into
     *
     * @var string
     */
    private $base_dir;

    /**
     * application's context
     *
     * @var string
     */

    private $context;

    /**
     * application's environment
     *
     * @var string
     */
    private $environment;

    /**
     * BootstrapResolver's constructor
     *
     * @param string $base_dir    base directory from where we define in which directory to look into
     * @param string $context     application's context
     * @param string $environment application's environment
     */
    public function __construct($base_dir, $context, $environment)
    {
        $this->base_dir = $base_dir;
        $this->context = null === $context ? BBApplication::DEFAULT_CONTEXT : $context;
        $this->environment = null === $environment ? BBApplication::DEFAULT_ENVIRONMENT : $environment;
    }

    /**
     * Returns an array which contains every parameters in bootstrap.yml file (use application context
     * and environment to find the right one)
     *
     * @return array of parameters coming from the bootstrap.yml file
     *
     * @throws BootstrapFileNotFoundException if bootstrap.yml is not found or not readable
     */
    public function getBootstrapParameters()
    {
        $bootstrap_filepath = null;

        $directories = $this->getBootstrapPotentialsDirectories();
        foreach ($directories as $directory) {
            $bootstrap_filepath = $directory . DIRECTORY_SEPARATOR . self::BOOTSTRAP_FILENAME;
            if (true === is_file($bootstrap_filepath) && true === is_readable($bootstrap_filepath)) {
                break;
            }

            $bootstrap_filepath = null;
        }

        if (null === $bootstrap_filepath) {
            throw new BootstrapFileNotFoundException($directories);
        }

        return Yaml::parse($bootstrap_filepath);
    }

    /**
     * Returns ordered directory (from specific to global) which can contains the bootstrap.yml file
     * according to context and environment passed in this class constructor
     *
     * @return array which contains every directory (string) where we can find the bootstrap.yml
     */
    public function getBootstrapPotentialsDirectories()
    {
        $bootstrap_directories = array();

        if (BBApplication::DEFAULT_CONTEXT !== $this->context) {
            if (BBApplication::DEFAULT_ENVIRONMENT !== $this->environment) {
                $bootstrap_directories[] = implode(DIRECTORY_SEPARATOR, array(
                    $this->base_dir, $this->context, 'Config', $this->environment
                ));
            }

            $bootstrap_directories[] = implode(DIRECTORY_SEPARATOR, array($this->base_dir, $this->context, 'Config'));
        }

        if (BBApplication::DEFAULT_ENVIRONMENT !== $this->environment) {
            $bootstrap_directories[] = implode(DIRECTORY_SEPARATOR, array(
                $this->base_dir, 'Config', $this->environment
            ));
        }

        $bootstrap_directories[] = $this->base_dir . DIRECTORY_SEPARATOR . 'Config';

        return $bootstrap_directories;
    }
}
