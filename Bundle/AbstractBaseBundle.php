<?php
namespace BackBuilder\Bundle;

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

use BackBuilder\Bundle\BundleInterface;
use BackBuilder\Config\Config;
use BackBuilder\IApplication as ApplicationInterface;

/**
 * Abstract class for bundle in BackBuilder5 application
 *
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class AbstractBaseBundle implements BundleInterface
{
    private $application;

    /**
     * [$base_directory description]
     * @var [type]
     */
    private $base_directory;

    /**
     * [$base_directory description]
     * @var [type]
     */
    private $id;

    /**
     * [$base_directory description]
     * @var [type]
     */
    private $config;

    /**
     * [$config_id description]
     * @var [type]
     */
    private $config_id;

    /**
     * [__construct description]
     * @param ApplicationInterface $application [description]
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->application = $application;
    }

    /**
     * [getBaseDirectory description]
     * @return [type] [description]
     */
    public function getBaseDirectory()
    {
        return $this->base_directory;
    }

    /**
     * [setBaseDirectory description]
     * @param [type] $base_directory [description]
     */
    public function setBaseDirectory($base_directory)
    {
        $this->base_directory = $base_directory;
        $this->id = basename($base_directory);

        return $this;
    }

    /**
     * [getConfig description]
     * @return [type] [description]
     */
    public function getConfig()
    {
        if (null === $this->application->getContainer()->hasDefinition($this->getConfigServiceId())) {
            $this->initConfig();
        }

        return $this->application->getContainer()->get($this->getConfigServiceId());
    }

    /**
     * [getConfigServiceId description]
     * @return [type] [description]
     */
    public function getConfigServiceId()
    {
        if (null === $this->config_id) {
            $this->config_id = str_replace('%bundle_id%', $this->id, BundleInterface::CONFIG_SERVICE_ID_PATTERN);
            $this->config_id = strtolower($this->config_id);
        }

        return $this->config_id;
    }

    /**
     * [getConfigDirectory description]
     * @return [type] [description]
     */
    public function getConfigDirectory()
    {
        if (null === $this->base_directory) {
            $this->defineBaseDirectoryAndId();
        }

        $directory = $this->base_directory . DIRECTORY_SEPARATOR . BundleInterface::CONFIG_DIRECTORY_NAME;
        if (false === is_dir($directory)) {
            $directory = $this->base_directory . DIRECTORY_SEPARATOR . BundleInterface::OLD_CONFIG_DIRECTORY_NAME;
        }

        return $directory;
    }

    /**
     * [initConfig description]
     * @return [type] [description]
     */
    private function initConfig()
    {
        if (false === $this->application->getContainer()->hasDefinition($this->getConfigServiceId())) {
            $definition = new Definition('BackBuilder\Config\Config', array(
                $this->getConfigDirectory(),
                new Reference('cache.bootstrap'),
                null,
                '%debug%',
                '%config.yml_files_to_ignore%'
            ));
            // $definition->addTag('dumpable');
            $definition->addMethodCall('setContainer', array(new Reference('service_container')));
            $definition->addMethodCall('setEnvironment', array('%bbapp.environment%'));
            $definition->setConfigurator(array(new Reference('bundle_config_configurator'), 'configure'));

            $this->application->getContainer()->setDefinition($this->getConfigServiceId());
        }
    }

    /**
     * [defineBaseDirectoryAndId description]
     * @return [type] [description]
     */
    private function defineBaseDirectoryAndId()
    {
        $reflection = new \ReflectionObject($this);
        $this->base_directory = dirname($reflection->getFileName());
        $this->id = basename($this->base_directory);
    }
}