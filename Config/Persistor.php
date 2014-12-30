<?php
namespace BackBee\Config;

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

use BackBee\Config\Exception\PersistorListNotFoundException;
use BackBee\Config\Persistor\PersistorInterface;
use BackBee\Exception\BBException;
use BackBee\Exception\InvalidArgumentException;
use BackBee\IApplication as ApplicationInterface;
use BackBee\Utils\Collection\Collection;

/**
 * Persistor allows us to handle with ease persistence of Config settings
 *
 * @category    BackBee
 * @package     BackBee\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Persistor
{
    const DEFAULT_CONFIG_PER_SITE_VALUE = false;

    /**
     * Application this persistor belongs to
     *
     * @var BackBee\BBApplication
     */
    private $application;

    /**
     * Configurator will provide the default settings of application and bundles configs
     *
     * @var BackBee\Config\Configurator
     */
    private $configurator;

    /**
     * List of declared persistor
     *
     * @var array
     */
    private $persistors;

    /**
     * Persistor's constructor
     *
     * @param ApplicationInterface $application  the application this persistor belongs to
     * @param Configurator         $configurator provider of config default settings
     */
    public function __construct(ApplicationInterface $application, Configurator $configurator)
    {
        $this->application = $application;
        $this->configurator = $configurator;
    }

    /**
     * Persist current settings $config
     *
     * @param Config  $config                 the config to persist
     * @param boolean $enable_config_per_site if true we only persist difference between current config settings
     *                                        and default config settings; default: false
     */
    public function persist(Config $config, $enable_config_per_site = self::DEFAULT_CONFIG_PER_SITE_VALUE)
    {
        if (null === $this->persistors) {
            $this->loadPersistors();
        }

        if (true === $enable_config_per_site) {
            $this->updateConfigOverridedSectionsForSite($config);
        }

        $this->doPersist($config, $config->getAllRawSections());

        // restore current config state after persist if config per site is enabled
        if (true === $enable_config_per_site) {
            $override_sections = $config->getRawSection('override_site');
            $site_uid = $this->application->getSite()->getUid();
            if (is_array($override_sections) && array_key_exists($site_uid, $override_sections)) {
                foreach ($override_sections[$site_uid] as $section_name => $section_settings) {
                    $config->setSection($section_name, $section_settings);
                }
            }
        }
    }

    /**
     * Tries to persist config by calling every declared persistors; it will stop on first success
     *
     * @param Config $config            the concern this persist action concern
     * @param array  $config_to_persist settings to persist for provided config
     */
    private function doPersist(Config $config, array $config_to_persist)
    {
        foreach ($this->persistors as $persistor) {
            if (true === $persistor->persist($config, $config_to_persist)) {
                $this->application->getContainer()->get('container.builder')->removeContainerDump();
                break;
            }
        }
    }

    /**
     * Loads every declared persistors in application config.yml, config section
     *
     * @throws PersistorListNotFoundException occurs if there is no persistor list in config.yml, section: config
     *                                        it also occurs if config section does not exist in application config.yml
     * @throws InvalidArgumentException       raises if one of declared persistors does not implement PersistorInterface
     */
    private function loadPersistors()
    {
        if (null !== $config = $this->application->getConfig()) {
            $config_config = $config->getConfigConfig();

            if (false === is_array($config_config) || false === array_key_exists('persistor', $config_config)) {
                throw new PersistorListNotFoundException();
            }

            $persistors = (array) $config_config['persistor'];
            foreach ($persistors as $persistor_classname) {
                $persistor = new $persistor_classname($this->application);
                if (false === ($persistor instanceof PersistorInterface)) {
                    throw new InvalidArgumentException(
                        get_class($persistor).' must implements BackBee\Config\Persistor\PersistorInterface'
                    );
                }

                $this->persistors[] = $persistor;
            }
        } else {
            throw new BBException('Application\'s Config must be different to null');
        }
    }

    /**
     * Add or update 'override_site' section to provided $config with difference between current config settings
     * and config default settings
     *
     * @param Config $config the config we want to add/update its 'override_site' section
     */
    private function updateConfigOverridedSectionsForSite(Config $config)
    {
        if (false === $this->application->isStarted()) {
            throw new BBException('Application is not started, we are not able to persist multiple site config.');
        }

        $default_sections = $this->configurator->getConfigDefaultSections($config);
        $current_sections = $config->getAllRawSections();

        $sections_to_update = array_keys(Collection::array_diff_assoc_recursive($default_sections, $current_sections));
        $sections_to_update = array_unique(array_merge(
            $sections_to_update,
            array_keys(Collection::array_diff_assoc_recursive($current_sections, $default_sections))
        ));

        $override_site = $config->getRawSection('override_site') ?: array();
        $site_uid = $this->application->getSite()->getUid();
        if (false === array_key_exists($site_uid, $override_site)) {
            $override_site[$site_uid] = array();
        }

        $override_sections_site = &$override_site[$site_uid];

        foreach ($sections_to_update as $section) {
            if ('override_site' !== $section) {
                $override_sections_site[$section] = $config->getRawSection($section);
            }
        }

        $config->deleteAllSections();
        foreach ($this->configurator->getConfigDefaultSections($config) as $section_name => $section_settings) {
            $config->setSection($section_name, $section_settings);
        }

        $config->setSection('override_site', $override_site, true);
    }
}
