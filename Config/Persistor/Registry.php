<?php
namespace BackBuilder\Config\Persistor;

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

use BackBuilder\Bundle\Registry as RegistryEntity;
use BackBuilder\Config\Config;
use BackBuilder\Config\Persistor\PersistorInterface;
use BackBuilder\IApplication as ApplicationInterface;

/**
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Registry implements PersistorInterface
{
    /**
     * [$application description]
     *
     * @var [type]
     */
    private $application;

    /**
     * @see BackBuilder\Config\Persistor\PersistorInterface::__construct
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->application = $application;
    }

    /**
     * @see BackBuilder\Config\Persistor\PersistorInterface::persist
     */
    public function persist(Config $config, array $config_to_persist)
    {
        if (true === array_key_exists('override_site', $config_to_persist)) {
            $config_to_persist = array(
                'override_site' => $config_to_persist['override_site']
            );
        }

        $key = basename(dirname($config->getBaseDir()));
        $scope = 'BUNDLE_CONFIG.' . $this->application->getContext() . '.' . $this->application->getEnvironment();

        $registry = $this->application->getEntityManager()
            ->getRepository('BackBuilder\Bundle\Registry')->findOneBy(array(
                'key'   => $key,
                'scope' => $scope
            ))
        ;

        if (null === $registry) {
            $registry = new RegistryEntity();
            $registry->setKey($key);
            $registry->setScope($scope);
            $this->application->getEntityManager()->persist($registry);
        }

        $registry->setValue(serialize($config_to_persist));
        $this->application->getEntityManager()->flush($registry);
    }
}