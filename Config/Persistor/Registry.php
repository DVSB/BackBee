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

namespace BackBee\Config\Persistor;

use BackBee\ApplicationInterface;
use BackBee\Bundle\Registry as RegistryEntity;
use BackBee\Config\Config;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Registry implements PersistorInterface
{
    /**
     * @var ApplicationInterface
     */
    private $app;

    /**
     * @see BackBee\Config\Persistor\PersistorInterface::__construct
     */
    public function __construct(ApplicationInterface $app)
    {
        $this->app = $app;
    }

    /**
     * @see BackBee\Config\Persistor\PersistorInterface::persist
     */
    public function persist(Config $config, array $configToPersist)
    {
        if (true === array_key_exists('override_site', $configToPersist)) {
            $configToPersist = array(
                'override_site' => $configToPersist['override_site'],
            );
        }

        $baseScope = 'BUNDLE_CONFIG.';
        $key = $this->app->getContainer()->get('bundle.loader')->getBundleIdByBaseDir($config->getBaseDir());
        if (null === $key) {
            $key = $application;
            $baseScope = 'APPLICATION_CONFIG.';
        }

        $scope = $baseScope.$this->app->getContext().'.'.$this->app->getEnvironment();

        $registry = $this->app->getEntityManager()
            ->getRepository('BackBee\Bundle\Registry')->findOneBy(array(
                'key'   => $key,
                'scope' => $scope,
            ))
        ;

        if (null === $registry) {
            $registry = new RegistryEntity();
            $registry->setKey($key);
            $registry->setScope($scope);
            $this->app->getEntityManager()->persist($registry);
        }

        $registry->setValue(serialize($configToPersist));
        $success = true;
        try {
            $this->app->getEntityManager()->flush($registry);
        } catch (\Exception $e) {
            $success = false;
        }

        return $success;
    }
}
