<?php
namespace BackBuilder;

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

use BackBuilder\Site\Site;
use BackBuilder\Console\Console;

/**
 * BackBuilder5 application interface
 *
 * @category    BackBuilder
 * @package     BackBuilder
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>, e.chau <eric.chau@lp-digital.fr>
 */
interface IApplication
{
    const DEFAULT_CONTEXT = 'default';
    const DEFAULT_ENVIRONMENT = '';

    /**
     * @param \BackBuilder\Site\Site $site
     */
    public function start(Site $site = null);

    /**
     * @return boolean
     */
    public function isStarted();

    /**
     * Stop the current BBApplication instance
     */
    public function stop();

    /**
     * Returns the starting context
     *
     * @return string
     */
    public function getContext();

    /**
     * Returns the starting context
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
     * @return BackBuilder\Config\Config
     */
    public function getConfig();

    /**
     * @return string
     */
    public function getConfigDir();

    /**
     * Returns path to Data directory
     *
     * @return string absolute path to Data directory
     */
    public function getDataDir();

    /**
     * @return BackBuilder\FrontController\FrontController
     */
    public function getController();

    /**
     * @return BackBuilder\Routing\RouteCollection
     */
    public function getRouting();

    /**
     * @return AutoLoader
     */
    public function getAutoloader();

    /**
     * @return BackBuilder\DependencyInjection\ContainerInterface
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
     * @return BackBuilder\Site\Site
     */
    public function getSite();

    /**
     *
     */
    public function registerCommands(Console $console);
}
