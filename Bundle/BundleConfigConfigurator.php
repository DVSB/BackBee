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

use BackBuilder\Config\Config;
use BackBuilder\Config\ConfigBuilder;
use BackBuilder\IApplication as ApplicationInterface;

/**
 *
 *
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class BundleConfigConfigurator
{
    /**
     * [$config_builder description]
     * @var [type]
     */
    private $config_builder;

    /**
     * [__construct description]
     * @param ApplicationInterface $application [description]
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->config_builder = new ConfigBuilder($application);
        $this->config_builder->setEntityManager($application->getEntityManager());
    }

    /**
     * [configure description]
     * @param  Config $config [description]
     * @return [type]         [description]
     */
    public function configure(Config $config)
    {
        $this->config_builder->extend(ConfigBuilder::BUNDLE_CONFIG, $config, array(
            'bundle_id' => basename(dirname($config->getBaseDir()))
        ));
    }
}