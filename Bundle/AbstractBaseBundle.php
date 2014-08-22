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
use BackBuilder\Security\Acl\Domain\IObjectIdentifiable;

/**
 * Abstract base class for BackBee's bundle
 *
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
abstract class AbstractBaseBundle implements BundleInterface
{
    /**
     * [$application description]
     *
     * @var BackBuilder\IApplication
     */
    private $application;

    /**
     * [$base_directory description]
     *
     * @var string
     */
    private $base_directory;

    /**
     * [$base_directory description]
     *
     * @var string
     */
    private $id;

    /**
     * [$base_directory description]
     *
     * @var BackBuilder\Config\Config
     */
    private $config;

    /**
     * [$config_id description]
     *
     * @var string
     */
    private $config_id;

    /**
     * [$started description]
     *
     * @var boolean
     */
    private $started;

    /**
     * AbstractBaseBundle's constructor
     *
     * @param ApplicationInterface $application [description]
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->application = $application;
        $this->started = false;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::getId
     */
    public function getId()
    {
        if (null === $this->id) {
            $this->defineBaseDirectoryAndId();
        }

        return $this->id;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::getBaseDirectory
     */
    public function getBaseDirectory()
    {
        return $this->base_directory;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::setBaseDirectory
     */
    public function setBaseDirectory($base_directory)
    {
        $this->base_directory = $base_directory;
        $this->id = basename($base_directory);

        return $this;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::getConfig
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
     *
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
     *
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
     * @see BackBuilder\Bundle\BundleInterface::getApplication
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::isStarted
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::started
     */
    public function started()
    {
        $this->started = true;
    }

    /**
     * @codeCoverageIgnore
     * @see Symfony\Component\Security\Acl\Model\DomainObjectInterface::getObjectIdentifier
     */
    public function getObjectIdentifier()
    {
        return $this->getType() . '(' . $this->getIdentifier() . ')';
    }

    /**
     * @codeCoverageIgnore
     * @see BackBuilder\Security\Acl\Domain\IObjectIdentifiable::getIdentifier
     */
    public function getIdentifier()
    {
        return $this->getId();
    }

    /**
     * @codeCoverageIgnore
     * @see BackBuilder\Security\Acl\Domain\IObjectIdentifiable::getType
     */
    public function getType()
    {
        return ClassUtils::getRealClass($this);
    }

    /**
     * @codeCoverageIgnore
     * @see BackBuilder\Security\Acl\Domain\IObjectIdentifiable::equals
     */
    public function equals(IObjectIdentifiable $identity)
    {
        return ($this->getType() === $identity->getType() && $this->getIdentifier() === $identity->getIdentifier());
    }

    /**
     * [initConfig description]
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
     */
    private function defineBaseDirectoryAndId()
    {
        $reflection = new \ReflectionObject($this);
        $this->base_directory = dirname($reflection->getFileName());
        $this->id = basename($this->base_directory);
    }
}
