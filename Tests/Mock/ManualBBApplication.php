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

namespace BackBee\Tests\Mock;

use BackBee\ApplicationInterface;
use BackBee\BBApplication;
use BackBee\Console\Console;
use BackBee\Site\Site;

/**
 * @category    BackBee
 *
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
     * @var BackBee\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var BackBee\Config\Config
     */
    protected $config;

    /**
     * @var BackBee\Site\Site
     */
    protected $site;

    /**
     * @var boolean
     */
    protected $isClientSAPI;

    /**
     * @var string[]
     */
    protected $resourceDir = [];

    /**
     * @var string[]
     */
    protected $classcontentDir = [];

    /**
     * ManualBBApplication's constructor.
     */
    public function __construct($context = null, $environment = null)
    {
        $this->is_started = false;
        $this->isClientSAPI = false;
        $this->context = null === $context ? BBApplication::DEFAULT_CONTEXT : $context;
        $this->environment = null === $environment ? BBApplication::DEFAULT_ENVIRONMENT : $environment;
        $this->overrided_config = false;
    }

    /**
     * __call allow us to catch everytime user wanted to set a value for a protected attribute;.
     *
     * @param string $method
     * @param array  $arguments
     */
    public function __call($method, $arguments)
    {
        if (1 === preg_match('#^set([a-zA-Z_]+)$#', $method, $matches) && 0 < count($matches)) {
            $property = strtolower($matches[1]);
            if (true === property_exists('BackBee\Tests\Mock\ManualBBApplication', $property)) {
                $this->$property = array_shift($arguments);
            }
        }
    }

    /**
     * @param \BackBee\Site\Site $site
     */
    public function start(Site $site = null)
    {
        return true === $this->is_started;
    }

    /**
     * Stop the current BBApplication instance.
     */
    public function stop()
    {
        return false === $this->is_started;
    }

    /**
     * Returns the starting context.
     *
     * @return string|NULL
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Returns the starting context.
     *
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
     * Returns path to Data directory.
     *
     * @return string absolute path to Data directory
     */
    public function getDataDir()
    {
    }

    /**
     * Return the resource directories, if undefined, initialized with common resources.
     *
     * @return string[] The resource directories.
     */
    public function getResourceDir()
    {
        return $this->resourceDir;
    }

    /**
     * Return the classcontent repositories path for this instance.
     *
     * @return string[]
     */
    public function getClassContentDir()
    {
        return $this->classcontentDir;
    }

    /**
     * @return ContainerBuilder
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return BackBee\Controller\FrontController
     */
    public function getController()
    {
    }

    /**
     * @return BackBee\Routing\RouteCollection
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

    /**
     * @return BackBee\Config\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return BackBee\Site\Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @return boolean
     */
    public function isStarted()
    {
        return $this->is_started;
    }
    
    public function isClientSAPI()
    {
        return $this->isClientSAPI;
    }
}
