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

namespace BackBee;

use BackBee\Console\Console;
use BackBee\Site\Site;

/**
 * BackBee5 application interface.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>, e.chau <eric.chau@lp-digital.fr>
 */
interface ApplicationInterface
{
    const DEFAULT_CONTEXT = 'default';
    const DEFAULT_ENVIRONMENT = '';

    /**
     * @param \BackBee\Site\Site $site
     */
    public function start(Site $site = null);

    /**
     * @return boolean
     */
    public function isStarted();

    /**
     * Stop the current BBApplication instance.
     */
    public function stop();

    /**
     * Returns the starting context.
     *
     * @return string
     */
    public function getContext();

    /**
     * Returns the starting context.
     *
     * @return string
     */
    public function getEnvironment();

    /**
     * @return string
     */
    public function getBBDir();

    /**
     * @return string
     */
    public function getBaseDir();

    /**
     * @return string
     */
    public function getBaseRepository();

    /**
     * @return string
     */
    public function getRepository();

    /**
     * @return BackBee\Config\Config
     */
    public function getConfig();

    /**
     * @return string
     */
    public function getConfigDir();

    /**
     * Returns path to Data directory.
     *
     * @return string absolute path to Data directory
     */
    public function getDataDir();

    /**
     * @return BackBee\Controller\FrontController
     */
    public function getController();

    /**
     * @return BackBee\Routing\RouteCollection
     */
    public function getRouting();

    /**
     * @return AutoLoader
     */
    public function getAutoloader();

    /**
     * @return BackBee\DependencyInjection\ContainerInterface
     */
    public function getContainer();

    /**
     * @return boolean
     */
    public function isOverridedConfig();

    /**
     * @return boolean
     */
    public function isDebugMode();

    /**
     * @return Site
     */
    public function getSite();

    /**
     * Is the BackBee application started as SAPI client?
     * 
     * @return boolean Returns true is application started as SAPI client, false otherwise
     */
    public function isClientSAPI();

    /**
     *
     */
    public function registerCommands(Console $console);
}
