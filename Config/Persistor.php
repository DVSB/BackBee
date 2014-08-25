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

use BackBuilder\Config\Configurator;
use BackBuilder\Config\Persistor\PersistorInterface;
use BackBuilder\IApplication as ApplicationInterface;
use BackBuilder\Util\Arrays;

/**
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Persistor
{
    const DEFAULT_CONFIG_PER_SITE_VALUE = false;

    /**
     * [$application description]
     *
     * @var [type]
     */
    private $application;

    /**
     * [$configurator description]
     *
     * @var [type]
     */
    private $configurator;

    /**
     * [$persistors description]
     * @var [type]
     */
    private $persistors;

    /**
     * [__construct description]
     *
     * @param ApplicationInterface $application  [description]
     * @param Configurator         $configurator [description]
     */
    public function __construct(ApplicationInterface $application, Configurator $configurator)
    {
        $this->application = $application;
        $this->configurator = $configurator;

        $this->loadApplicationPersistors();
    }

    /**
     * [persist description]
     * @param  Config $config                 [description]
     * @param  [type] $enable_config_per_site [description]
     * @return [type]                         [description]
     */
    public function persist(Config $config, $enable_config_per_site = self::DEFAULT_CONFIG_PER_SITE_VALUE)
    {
        if (true === $enable_config_per_site) {
            $this->updateConfigOverridedSectionsForSite($config);
        }

        $this->doPersist($config, $config->getAllRawSections());
    }

    /**
     * [doPersist description]
     * @param  Config $config            [description]
     * @param  array  $config_to_persist [description]
     * @return [type]                    [description]
     */
    private function doPersist(Config $config, array $config_to_persist)
    {
        foreach ($this->persistors as $persistor) {
            if (true === $persistor->persist($config, $config_to_persist)) {
                break;
            }
        }
    }

    /**
     * [loadApplicationPersistors description]
     * @return [type] [description]
     */
    private function loadApplicationPersistors()
    {
        if (null !== $config = $this->application->getConfig()) {
            $config_config = $config->getConfigConfig();
            if (false === array_key_exists('persistor', $config_config)) {
                throw new \Exception('You has to specify persistor!');
            }

            $persistors = (array) $config_config['persistor'];
            foreach ($persistors as $persistor_classname) {
                $persistor = new $persistor_classname($this->application);
                if (false === ($persistor instanceof PersistorInterface)) {
                    throw new \Exception('must implements PersistorInterface');
                }

                $this->persistors[] = $persistor;
            }
        }
    }

    /**
     * [updateConfigOverridedSectionsForSite description]
     *
     * @param  Config $config [description]
     */
    private function updateConfigOverridedSectionsForSite(Config $config)
    {
        if (false === $this->application->isStarted()) {
            throw new \Exception('Application is not started, we are not able to persist multiple site config.');
        }

        $default_sections = $this->configurator->getConfigDefaultSections($config);
        $current_sections = $config->getAllRawSections();

        $sections_to_update = array_keys(Arrays::array_diff_assoc_recursive($default_sections, $current_sections));
        $sections_to_update = array_unique(array_merge(
            $sections_to_update,
            array_keys(Arrays::array_diff_assoc_recursive($current_sections, $default_sections))
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
                $config->setSection($section, $default_sections[$section], true);
            }
        }

        $config->setSection('override_site', $override_site, true);
    }
}
