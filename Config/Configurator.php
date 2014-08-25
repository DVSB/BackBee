<?php
namespace BackBuilder\Config;

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

use BackBuilder\IApplication;
use BackBuilder\Bundle\Registry;
use BackBuilder\Config\Config;
use BackBuilder\Config\Exception\InvalidConfigTypeException;
use BackBuilder\DependencyInjection\Dumper\DumpableServiceProxyInterface;
use BackBuilder\Event\Event;
use BackBuilder\Util\Resolver\BundleConfigDirectory;
use BackBuilder\Util\Resolver\ConfigDirectory;

use Doctrine\ORM\EntityManager;

/**
 * Allow us to build and extend config depending on the type (which can be equals to
 * self::APPLICATION_CONFIG and self::BUNDLE_CONFIG)
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Configurator
{
    /**
     * constants we need to define which type of extend and build we have to perform;
     * indeed, we do different stuff depending on if it's a config for application or
     * bundle
     */
    const APPLICATION_CONFIG = 0;
    const BUNDLE_CONFIG = 1;

    /**
     * [$application description]
     *
     * @var [type]
     */
    private $application;

    /**
     * BackBee base directory
     *
     * @var string
     */
    private $bb_directory;

    /**
     * repository base directory
     *
     * @var string
     */
    private $base_repository;

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
     * define if we should override config or not on extend
     *
     * @var boolean
     */
    private $override_config;

    /**
     * [$configs_default_sections description]
     *
     * @var [type]
     */
    private $configs_default_sections;

    /**
     * Configurator's constructor
     *
     * @param boolean $override_config define if we should override config on extend()
     */
    public function __construct(IApplication $application)
    {
        $this->application = $application;
        $this->bb_directory = $application->getBBDir();
        $this->base_repository = $application->getBaseRepository();
        $this->context = $application->getContext();
        $this->environment = $application->getEnvironment();
        $this->override_config = $application->isOverridedConfig();
        $this->configs_default_sections = array();
    }

    /**
     * Do every extend according to application context, environment and config target type (APPLICATION OR BUNDLE)
     *
     * @param integer $type    define which kind of extend we want to apply
     *                         (self::APPLICATION_CONFIG or self::BUNDLE_CONFIG)
     * @param Config  $config  the config we want to extend
     * @param array   $options options for extend config action
     */
    public function extend($type, Config $config, $options = array())
    {
        if (false === ($config instanceof DumpableServiceProxyInterface) || false === $config->isRestored()) {
            if (self::APPLICATION_CONFIG === $type) {
                $this->doApplicationConfigExtend($config);
            } elseif (self::BUNDLE_CONFIG === $type) {
                $this->doBundleConfigExtend($config, $options);
            } else {
                throw new InvalidConfigTypeException('extend', $type);
            }
        }
    }

    /**
     * [configureApplicationConfig description]
     *
     * @param  Config $config [description]
     */
    public function configureApplicationConfig(Config $config)
    {
        $this->extend(self::APPLICATION_CONFIG, $config);
        $this->storeConfigDefaultSections($config);
    }

    /**
     * [configureBundleConfig description]
     * @param  Config $config [description]
     * @return [type]         [description]
     */
    public function configureBundleConfig(Config $config)
    {
        $this->extend(self::BUNDLE_CONFIG, $config, array('bundle_id' => basename(dirname($config->getBaseDir()))));
        $this->storeConfigDefaultSections($config);
    }

    /**
     * [getConfigDefaultSections description]
     *
     * @param  Config $config [description]
     *
     * @return [type]         [description]
     */
    public function getConfigDefaultSections(Config $config)
    {
        $index = spl_object_hash($config);
        $default_sections = null;
        if (true === array_key_exists($index, $this->configs_default_sections)) {
            $default_sections = $this->configs_default_sections[$index];
        }

        return $default_sections;
    }

    /**
     * [onGetServiceConfig description]
     *
     * @param  Event  $event [description]
     */
    public function onGetServiceConfig(Event $event)
    {
        if (true === $this->application->isStarted()) {
            $config = $event->getTarget();
            if (null !== $override_site = $config->getRawSection('override_site')) {
                if (true === array_key_exists($this->application->getSite()->getUid(), $override_site)) {
                    foreach ($override_site[$this->application->getSite()->getUid()] as $section => $data) {
                        $config->setSection($section, $data, true);
                    }
                }
            }

            $this->application->getContainer()->getDefinition($event->getArgument('id'))->clearTag('config_per_site');
        }
    }

    /**
     * [storeConfigDefaultSections description]
     *
     * @param  Config $config [description]
     */
    private function storeConfigDefaultSections(Config $config)
    {
        $this->configs_default_sections[spl_object_hash($config)] = $config->getAllRawSections();
    }

    /**
     * Do every extend according to application context and envrionment for application's Config
     *
     * @param  Config $config config we want to apply Application Config extend type
     */
    private function doApplicationConfigExtend(Config $config)
    {
        $config_directories = ConfigDirectory::getDirectories(
            null,
            $this->base_repository,
            $this->context,
            $this->environment
        );

        foreach ($config_directories as $directory) {
            if (true === is_dir($directory)) {
                $config->extend($directory, $this->override_config);
            }
        }
    }

    /**
     * [doBundleConfigExtend description]
     *
     * @param  Config $config  [description]
     * @param  array  $options [description]
     *
     * @return [type]          [description]
     */
    private function doBundleConfigExtend(Config $config, array $options)
    {
        $this->overrideConfigByFile($config, $options['bundle_id']);
        $config_config = $this->application->getConfig()->getConfigConfig();
        if (!array_key_exists('save_in_registry', $config_config) || true === $config_config['save_in_registry']) {
            $this->overrideConfigByRegistry($config, $options['bundle_id']);
        }
    }

    /**
     * [overrideConfigByFile description]
     *
     * @param  Config $config    [description]
     * @param  [type] $bundle_id [description]
     */
    private function overrideConfigByFile(Config $config, $bundle_id)
    {
        $directories = BundleConfigDirectory::getDirectories(
            $this->base_repository,
            $this->context,
            $this->environment,
            $bundle_id
        );

        foreach ($directories as $directory) {
            $config->extend($directory, true);
        }
    }

    /**
     * [overrideConfigByRegistry description]
     *
     * @param  Config $config [description]
     * @param  [type] $bundle_id     [description]
     */
    private function overrideConfigByRegistry(Config $config, $bundle_id)
    {
        $registry = $this->getRegistryConfig($bundle_id);
        if (null !== $registry) {
            $registry_config = @unserialize($serialized);

            if (true === is_array($registry_config)) {
                foreach ($registry_config as $section => $value) {
                    $config->setSection($section, $value, true);
                }
            }
        }
    }

    /**
     * Returns the registry entry for bundle's config storing
     *
     * @param string $bundle_id the id of the bundle we are looking for override config in registry
     *
     * @return \BackBuilder\Bundle\Registry
     */
    private function getRegistryConfig($bundle_id)
    {
        $registry = null;
        if(null !== $em = $this->application->getEntityManager()) {
            $registry = $em->getRepository('BackBuilder\Bundle\Registry')->findOneBy(array(
                'key' => $bundle_id,
                'scope' => 'BUNDLE_CONFIG.' . $this->context . '.' . $this->environment
            ));
        }

        return $registry;
    }
}
