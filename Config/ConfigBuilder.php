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
use BackBuilder\Config\Config;
use BackBuilder\Config\Exception\InvalidConfigTypeException;
use BackBuilder\DependencyInjection\Dumper\DumpableServiceProxyInterface;
use BackBuilder\Util\Resolver\ConfigDirectory;

/**
 * Allow us to build and extend config depending on the type (which can be equals to
 * self::APPLICATION_CONFIG and self::BUNDLE_CONFIG)
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ConfigBuilder
{
    /**
     * constants we need to define which type of extend and build we have to perform;
     * indeed, we do different stuff depending on if it's a config for application or
     * bundle
     */
    const APPLICATION_CONFIG = 0;
    const BUNDLE_CONFIG = 1;

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
     * ConfigBuilder's constructor
     *
     * @param boolean $override_config define if we should override config on extend()
     */
    public function __construct(IApplication $application)
    {
        $this->bb_directory = $application->getBBDir();
        $this->base_repository = $application->getBaseRepository();
        $this->context = $application->getContext();
        $this->environment = $application->getEnvironment();
        $this->override_config = $application->isOverridedConfig();
    }

    /**
     * Do every extend according to application context, environment and config target type (APPLICATION OR BUNDLE)
     *
     * @param  integer $type   define which kind of extend we want to apply
     *                         (self::APPLICATION_CONFIG or self::BUNDLE_CONFIG)
     * @param  Config  $config the config we want to extend
     */
    public function extend($type, Config $config)
    {
        if (false === ($config instanceof DumpableServiceProxyInterface) || false === $config->isRestored()) {
            if (self::APPLICATION_CONFIG === $type) {
                $this->doApplicationConfigExtend($config);
            } elseif (self::BUNDLE_CONFIG === $type) {
                /* to define */
            } else {
                throw new InvalidConfigTypeException('extend', $type);
            }
        }
    }

    /**
     * Do every extend according to application context and envrionment for application's Config
     *
     * @param  Config $config config we want to apply Application Config extend type
     */
    private function doApplicationConfigExtend(Config $config)
    {
        $config_directories = (new ConfigDirectory())->getDirectories(
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
}
