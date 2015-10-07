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

namespace BackBee\Config;

use Doctrine\DBAL\DBALException;
use BackBee\ApplicationInterface;
use BackBee\Bundle\BundleLoader;
use BackBee\Config\Exception\InvalidConfigTypeException;
use BackBee\Event\Event;
use BackBee\Util\Resolver\BundleConfigDirectory;
use BackBee\Util\Resolver\ConfigDirectory;

/**
 * Allow us to build and extend config depending on the type (which can be equals to
 * self::APPLICATION_CONFIG and self::BUNDLE_CONFIG).
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Configurator
{
    /**
     * constants we need to define which type of extend and build we have to perform;
     * indeed, we do different stuff depending on if it's a config for application or
     * bundle.
     */
    const APPLICATION_CONFIG = 0;
    const BUNDLE_CONFIG = 1;

    /**
     * @var ApplicationInterface
     */
    private $application;

    /**
     * @var BundleLoader
     */
    private $bundleLoader;

    /**
     * repository base directory.
     *
     * @var string
     */
    private $baseRepository;

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
     * define if we should override config or not on extend.
     *
     * @var boolean
     */
    private $overrideConfig;

    /**
     * @var array
     */
    private $configsDefaultSections = [];

    /**
     * Configurator's constructor.
     *
     * @param boolean $overrideConfig define if we should override config on extend()
     */
    public function __construct(ApplicationInterface $application, BundleLoader $bundleLoader)
    {
        $this->application = $application;
        $this->bundleLoader = $bundleLoader;
        $this->baseRepository = $application->getBaseRepository();
        $this->context = $application->getContext();
        $this->environment = $application->getEnvironment();
        $this->overrideConfig = $application->isOverridedConfig();
    }

    /**
     * Do every extend according to application context, environment and config target type (APPLICATION OR BUNDLE).
     *
     * @param integer $type    define which kind of extend we want to apply
     *                         (self::APPLICATION_CONFIG or self::BUNDLE_CONFIG)
     * @param Config  $config  the config we want to extend
     * @param array   $options options for extend config action
     */
    public function extend($type, Config $config, $options = array())
    {
        if (false === $config->isRestored()) {
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
     * @param Config $config
     */
    public function configureApplicationConfig(Config $config)
    {
        $this->extend(self::APPLICATION_CONFIG, $config);
        $this->storeConfigDefaultSections($config);
    }

    /**
     * @param Config $config
     */
    public function configureBundleConfig(Config $config)
    {
        $this->extend(self::BUNDLE_CONFIG, $config, [
            'bundle_id' => $this->bundleLoader->getBundleIdByBaseDir($config->getBaseDir()),
        ]);
        $this->storeConfigDefaultSections($config);
    }

    /**
     * @param Config $config
     *
     * @return array
     */
    public function getConfigDefaultSections(Config $config)
    {
        $index = spl_object_hash($config);
        $default_sections = [];
        if (true === array_key_exists($index, $this->configsDefaultSections)) {
            $default_sections = $this->configsDefaultSections[$index];
        }

        return $default_sections;
    }

    /**
     * @param Event $event
     */
    public function onGetServiceConfig(Event $event)
    {
        if (true === $this->application->isStarted()) {
            $config = $event->getTarget();
            if (null !== $override_site = $config->getRawSection('override_site')) {
                if (array_key_exists($this->application->getSite()->getUid(), $override_site)) {
                    foreach ($override_site[$this->application->getSite()->getUid()] as $section => $data) {
                        $config->setSection($section, $data, true);
                    }
                }
            }

            $this->application->getContainer()->getDefinition($event->getArgument('id'))->clearTag('config_per_site');
        }
    }

    /**
     * @param Config $config
     */
    private function storeConfigDefaultSections(Config $config)
    {
        $this->configsDefaultSections[spl_object_hash($config)] = $config->getAllRawSections();
    }

    /**
     * Do every extend according to application context and envrionment for application's Config.
     *
     * @param Config $config config we want to apply Application Config extend type
     */
    private function doApplicationConfigExtend(Config $config)
    {
        $config_directories = ConfigDirectory::getDirectories(
            null,
            $this->baseRepository,
            $this->context,
            $this->environment
        );

        foreach ($config_directories as $directory) {
            if (is_dir($directory)) {
                $config->extend($directory, $this->overrideConfig);
            }
        }
    }

    /**
     * @param Config $config
     * @param array  $options
     */
    private function doBundleConfigExtend(Config $config, array $options)
    {
        $this->overrideConfigByFile($config, $options['bundle_id']);
        $configConfig = $this->application->getConfig()->getConfigConfig();
        if (
            null === $configConfig
            || array_key_exists('save_in_registry', $configConfig)
            || true === $configConfig['save_in_registry']
        ) {
            $this->overrideConfigByRegistry($config, $options['bundle_id']);
        }
    }

    /**
     * @param Config $config
     * @param string $bundleId
     */
    private function overrideConfigByFile(Config $config, $bundleId)
    {
        $directories = BundleConfigDirectory::getDirectories(
            $this->baseRepository,
            $this->context,
            $this->environment,
            $bundleId
        );

        foreach ($directories as $directory) {
            $config->extend($directory, true);
        }
    }

    /**
     * @param Config $config
     * @param string $bundleId
     */
    private function overrideConfigByRegistry(Config $config, $bundleId)
    {
        $registry = $this->getRegistryConfig($bundleId);
        if (null !== $registry) {
            $registryConfig = @unserialize($registry->getValue());
            if (is_array($registryConfig)) {
                foreach ($registryConfig as $section => $value) {
                    $config->setSection($section, $value, true);
                }
            }
        }
    }

    /**
     * Returns the registry entry for bundle's config storing.
     *
     * @param string $bundleId the id of the bundle we are looking for override config in registry
     *
     * @return \BackBee\Bundle\Registry
     */
    private function getRegistryConfig($bundleId)
    {
        $registry = null;

        try {
            if (null !== $em = $this->application->getEntityManager()) {
                $registry = $em->getRepository('BackBee\Bundle\Registry')->findOneBy(array(
                    'key' => $bundleId,
                    'scope' => 'BUNDLE_CONFIG.'.$this->context.'.'.$this->environment,
                ));
            }
        } catch (DBALException $e) {
            $expectedError = false;
            if (null !== $e->getPrevious() && $e->getPrevious() instanceof \PDOException) {
                // expected error is if we try to get overrided config in registry on application installation process
                // PDOException has two methods for retrieving information about an error
                // @see http://php.net/manual/en/class.pdoexception.php
                $expectedError = (
                    '42S02' === $e->getPrevious()->getCode()
                    || false !== strpos($e->getPrevious()->getMessage(), 'SQLSTATE[42S02]')
                );
            }

            if (false === $expectedError) {
                throw $e;
            }
        }

        return $registry;
    }
}
