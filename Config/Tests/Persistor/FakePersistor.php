<?php

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

namespace BackBuilder\Config\Tests\Persistor;

use BackBuilder\Config\Config;
use BackBuilder\Config\Persistor\PersistorInterface;
use BackBuilder\IApplication as ApplicationInterface;

/**
 * Fake persistor used for test
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class FakePersistor implements PersistorInterface
{
    /**
     * Application's container
     * @var BackBuilder\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->container = $application->getContainer();
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Config $config, array $config_to_persist)
    {
        $this->container->setParameter('config_to_persist', $config_to_persist);

        return true;
    }
}
