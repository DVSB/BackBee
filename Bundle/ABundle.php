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

namespace BackBuilder\Bundle;

use BackBuilder\BBApplication;
use BackBuilder\Bundle\AbstractBaseBundle;
use BackBuilder\Bundle\Exception\BundleException;
use BackBuilder\Logging\Logger;
use BackBuilder\Routing\RouteCollection as Routing;
use BackBuilder\Util\Arrays;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManager;

/**
 * Abstract class for bundle in BackBuilder5 application
 *
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>, e.chau <eric.chau@lp-digital.fr>
 */
abstract class ABundle extends AbstractBaseBundle
{
    private $_em;
    private $_logger;
    private $_routing;
    private $_request;

    /**
     *
     * @var \ReflectionObject
     */
    protected $reflected;

    public function __call($method, $args)
    {
        if (null !== $this->getLogger()) {
            if (true === is_array($args) && 0 < count($args)) {
                $args[0] = sprintf('[%s] %s', $this->getId(), $args[0]);
            }

            call_user_func_array(array($this->getLogger(), $method), $args);
        }
    }

    public function __construct(BBApplication $application, Logger $logger = null)
    {
        parent::__construct($application);

        $this->_logger = $logger;

        if (null === $this->_logger) {
            $this->_logger = $this->getApplication()->getLogging();
        }
    }

    /**
     * Returns an Entity Manager for the bundle
     * If a valid doctrine configuration is provided, a new connection is established
     * If none doctrine configuration is provided, returns the BBApplication entity manager
     * @param array $doctrine_config Connection informations
     * @return \Doctrine\ORM\EntityManager
     * @throws \BackBuilder\Bundle\Exception\BundleException Occure on database connection error
     */
    private function _initEntityManager($doctrine_config = null)
    {
        if (null === $doctrine_config || false === array_key_exists('dbal', $doctrine_config)) {
            return $this->getApplication()->getEntityManager();
        }

        try {
            if (false === array_key_exists('proxy_ns', $doctrine_config['dbal'])) {
                $doctrine_config['dbal']['proxy_ns'] = 'Proxies';
            }

            if (false === array_key_exists('proxy_dir', $doctrine_config['dbal'])) {
                $doctrine_config['dbal']['proxy_dir'] = $this->getApplication()->getCacheDir() . DIRECTORY_SEPARATOR . 'Proxies';
            }

            $em = \BackBuilder\Util\Doctrine\EntityManagerCreator::create($doctrine_config['dbal'], $this->getLogger(), $this->getApplication()->getEntityManager()->getEventManager());
        } catch (\Exception $e) {
            throw new Exception\BundleException('Database connection error', BundleException::INIT_ERROR, $e);
        }

        return $em;
    }

    /**
     * Get entity manager for this bundle
     *
     * This manager includes only this bundle's entities
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getBundleEntityManager()
    {
        $doctrineConfig = $this->getConfig()->getDoctrineConfig();

        if(null === $doctrineConfig) {
            $doctrineConfig = $this->getApplication()->getConfig()->getDoctrineConfig();
        }

        if (false === array_key_exists('proxy_ns', $doctrineConfig['dbal'])) {
            $doctrineConfig['dbal']['proxy_ns'] = 'Proxies';
        }

        if (false === array_key_exists('proxy_dir', $doctrineConfig['dbal'])) {
            $doctrineConfig['dbal']['proxy_dir'] = $this->getApplication()->getCacheDir() . '/Proxies';
        }

        $em = $this->_initEntityManager($doctrineConfig);

        // set the path to include only this bundle's entities
        if(false === file_exists($this->getBaseDir() . '/Entity')) {
            // Entity dir doesn't exist
            mkdir($this->getBaseDir() . '/Entity');
        }
        $em->getConfiguration()->getMetadataDriverImpl()->addPaths(array($this->getBaseDir() . '/Entity'));


        return $em;
    }

    /**
     * Install this bundle
     */
    public function install()
    {
        // create DB tables
        $bundleEm = $this->getBundleEntityManager();

        $metadata = $bundleEm->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($this->getEntityManager());
        $schema->createSchema($metadata);
    }

    /**
     * Update this bundle
     */
    public function update()
    {
        // update DB tables
        $bundleEm = $this->getBundleEntityManager();

        $metadata = $bundleEm->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($this->getEntityManager());
        $schema->updateSchema($metadata, true);
    }

    /**
     * Get update queries
     * @param EntityManager $em
     * @return String[]
     */
    public function getUpdateQueries(EntityManager $em)
    {
        $schema = new SchemaTool($em);

        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $sqls = $schema->getUpdateSchemaSql($metadatas, true);

        return $sqls;
    }

    /**
     * Get create queries
     * @param EntityManager $em
     * @return String[]
     */
    public function getCreateQueries(EntityManager $em)
    {
        $schema = new SchemaTool($em);

        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $sqls = $schema->getCreateSchemaSql($metadatas);

        return $sqls;
    }

    private function _initRouting()
    {
        $routing = $this->getConfig()->getRoutingConfig();
        if (is_null($routing)) {
            $this->_routing = false;
        }

        $this->_routing = new Routing($this->getApplication());
        $this->_routing->addBundleRouting($this);

        return $this;
    }

    /**
     * Returns the entity manager used by the bundle
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        if (null === $this->_em) {
            $this->_em = $this->_initEntityManager($this->getConfig()->getDoctrineConfig());
        }

        return $this->_em;
    }

    /**
     * @codeCoverageIgnore
     * @return Logger
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * @codeCoverageIgnore
     * @return path
     */
    public function getBaseDir()
    {
        return $this->getBaseDirectory();
    }

    /**
     * @codeCoverageIgnore
     * @return path
     */
    public function getResourcesDir()
    {
        //return $this->getBaseDir() . DIRECTORY_SEPARATOR . 'Ressources';

        $resources_dir = $this->getBaseDir() . DIRECTORY_SEPARATOR . 'Ressources';
        if ('test' === $this->getApplication()->getEnvironment()) {
            $test_dir = $this->getBaseDir() . DIRECTORY_SEPARATOR . 'Test' . DIRECTORY_SEPARATOR . 'Ressources';
            if (true === file_exists($test_dir)) {
                $resources_dir = $test_dir;
            }
        }

        return $resources_dir;
    }

    /**
     * [getRouting description]
     * @return [type] [description]
     */
    public function getRouting()
    {
        if (null === $this->_routing) {
            $this->_initRouting();
        }

        return $this->_routing;
    }

    /**
     * Returns the current request
     * @access public
     * @return Request
     */
    public function getRequest()
    {
        if (null === $this->_request) {
            $this->_request = $this->getApplication()->getContainer()->get('request');
        }

        return $this->_request;
    }

    public function setLogger(Logger $logger)
    {
        $this->_logger = $logger;

        return $this;
    }

    public function unserialize($serialized)
    {
    }

    /**
     * Get the Bundle namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        if (null === $this->reflected) {
            $this->reflected = new \ReflectionObject($this);
        }

        return $this->reflected->getNamespaceName();
    }
}
