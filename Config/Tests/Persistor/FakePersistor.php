<?php

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

namespace BackBee\Config\Tests\Persistor;

use BackBee\Config\Config;
use BackBee\Config\Persistor\PersistorInterface;
use BackBee\IApplication as ApplicationInterface;

/**
 * Fake persistor used for test
 *
 * @category    BackBee
 * @package     BackBee\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class FakePersistor implements PersistorInterface
{
    /**
     * Application's container
     * @var BackBee\DependencyInjection\ContainerInterface
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
