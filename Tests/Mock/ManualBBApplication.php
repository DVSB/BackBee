<?php
namespace BackBuilder\Tests\Mock;

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
use BackBuilder\IApplication as ApplicationInterface;
use BackBuilder\Console\Console;
use BackBuilder\Site\Site;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Tests\Mock
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ManualBBApplication implements ApplicationInterface
{
    /**
     * @var boolean
     */
    protected $is_started;

    /**
     * @var string
     */
    protected $context;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var string
     */
    protected $bb_dir;

    /**
     * @var string
     */
    protected $base_dir;

    /**
     * @var string
     */
    protected $base_repository;

    /**
     * @var string
     */
    protected $repository;

    /**
     * @var string
     */
    protected $config_dir;

    /**
     * @var boolean
     */
    protected $overrided_config;

    /**
     * @var boolean
     */
    protected $debug_mode;

    /**
     * @var BackBuilder\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * ManualBBApplication's constructor
     */
    public function __construct($context = null, $environment = null)
    {
        $this->is_started = false;
        $this->context = null === $context ? BBApplication::DEFAULT_CONTEXT : $context;
        $this->environment = null === $environment ? BBApplication::DEFAULT_ENVIRONMENT : $environment;
        $this->overrided_config = false;
    }

    /**
     * __call allow us to catch everytime user wanted to set a value for a protected attribute;
     *
     * @param  string $method
     * @param  array  $arguments
     */
    public function __call($method, $arguments)
    {
        if (1 === preg_match('#^set([a-zA-Z_]+)$#', $method, $matches) && 0 < count($matches)) {
            $property = strtolower($matches[1]);
            if (true === property_exists('BackBuilder\Tests\Mock\ManualBBApplication', $property)) {
                $this->$property = array_shift($arguments);
            }
        }
    }

    /**
     * @param \BackBuilder\Site\Site $site
     */
    public function start(Site $site = null)
    {
        return true === $this->is_started;
    }

    /**
     * Stop the current BBApplication instance
     */
    public function stop()
    {
        return false === $this->is_started;
    }

    /**
     * Returns the starting context
     * @return string|NULL
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Returns the starting context
     * @return string|NULL
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return string
     */
    public function getBBDir()
    {
        return $this->bb_dir;
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return $this->base_dir;
    }

    /**
     * @return string
     */
    public function getBaseRepository()
    {
        return $this->base_repository;
    }

    /**
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return string
     */
    public function getConfigDir()
    {
        return $this->config_dir;
    }

    /**
     * Returns path to Data directory
     *
     * @return string absolute path to Data directory
     */
    public function getDataDir()
    {
    }

    /**
     * @return ContainerBuilder
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return BackBuilder\FrontController\FrontController
     */
    public function getController()
    {
    }

    /**
     * @return BackBuilder\Routing\RouteCollection
     */
    public function getRouting()
    {
    }

    /**
     * @return AutoLoader
     */
    public function getAutoloader()
    {
    }

    /**
     *
     */
    public function registerCommands(Console $console)
    {
    }

   /**
     * @return boolean
     */
    public function isOverridedConfig()
    {
        return $this->overrided_config;
    }

    /**
     * @return boolean
     */
    public function isDebugMode()
    {
        return $this->debug_mode;
    }
}
