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

use BackBuilder\BBApplication,
    BackBuilder\Config\Config,
    BackBuilder\Routing\RouteCollection as Routing,
    BackBuilder\Logging\Logger,
    BackBuilder\Security\Acl\Domain\IObjectIdentifiable,
    BackBuilder\Util\Arrays,
    BackBuilder\Bundle\Exception\BundleException;
use Symfony\Component\Security\Core\Util\ClassUtils,
    Symfony\Component\Yaml\Yaml;

use Doctrine\ORM\Tools\SchemaTool,
    Doctrine\ORM\EntityManager;

/**
 * Abstract class for bundle in BackBuilder5 application
 *
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
abstract class ABundle implements IObjectIdentifiable, \Serializable
{

    private $_id;
    private $_application;
    private $_em;
    private $_logger;
    private $_basedir;
    private $_config;
    private $_properties;
    private $_routing;
    private $_request;

    /**
     * @var array
     */
    private $configDefaultSections;

    /**
     * @var boolean
     */
    private $manageMultisiteConfig = true;

    /**
     * @var boolean
     */
    private $isConfigFullyInit = false;
    
    /**
     *
     * @var \ReflectionObject
     */
    protected $reflected;

    public function __call($method, $args)
    {
        if (NULL !== $this->getLogger()) {
            if (true === is_array($args) && 0 < count($args)) {
                $args[0] = sprintf('[%s] %s', $this->getId(), $args[0]);
            }

            call_user_func_array(array($this->getLogger(), $method), $args);
        }
    }

    public function __construct(BBApplication $application, Logger $logger = null)
    {
        $this->_application = $application;

        $r = new \ReflectionObject($this);
        $this->_basedir = dirname($r->getFileName());
        $this->_id = basename($this->_basedir);

        $this->_logger = $logger;
        if (NULL === $this->_logger)
            $this->_logger = $this->_application->getLogging();
    }

    private function _initConfig($configdir = null)
    {
        if (true === is_null($configdir)) {
            $configdir = $this->getResourcesDir();
        }

        $this->_config = new Config($configdir, $this->getApplication()->getBootstrapCache());

        // Looking for bundle's config in registry
        $registry = $this->_getRegistryConfig();
        if (null !== $serialized = $registry->getValue()) {
            $registryConfig = @unserialize($serialized);

            if (true === is_array($registryConfig)) {
                foreach ($registryConfig as $section => $value) {
                    $this->_config->setSection($section, $value, true);
                }
            }
        }

        $this->_config->setContainer($this->getApplication()->getContainer());
        $allSections = $this->_config->getAllRawSections();
        $this->configDefaultSections = $allSections;
        if (
                true === array_key_exists('bundle', $allSections) &&
                true === array_key_exists('manage_multisite', $allSections['bundle']) &&
                false === $allSections['bundle']['manage_multisite']
        ) {
            $this->manageMultisiteConfig = false;
        }

        return $this;
    }

    /**
     * Returns an Entity Manager for the bundle
     * If a valid doctrine configuration is provided, a new connection is established
     * If none doctrine configuration is provided, returns the BBApplication entity manager
     * @param array $doctrine_config Connection informations
     * @return \Doctrine\ORM\EntityManager
     * @throws \BackBuilder\Bundle\Exception\BundleException Occure on database connection error
     */
    private function _initEntityManager($doctrine_config = NULL)
    {
        if (NULL === $doctrine_config || false === array_key_exists('dbal', $doctrine_config)) {
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
        $em->getConfiguration()->getMetadataDriverImpl()->addPaths(array($this->getBaseDir() . '/Entity'));
        
        return $em;
    }
    
    
    public function install()
    {
        // create DB tables
        $em = $this->getBundleEntityManager();
        
        $schema = new SchemaTool($em);
        
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema->createSchema($metadata, true);
        
        $em->getConnection()->commit();
    }
    
    public function update()
    {
        // update DB tables
        $em = $this->getBundleEntityManager();
        
        $schema = new SchemaTool($em);
        
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema->updateSchema($metadata, false);
    }
    
    /**
     * Get update queries
     * @param EntityManager $em
     * @return String[]
     */
    public function getUpdateQueries(EntityManager $em)
    {
        $schema = new SchemaTool($em);
        
        $metadatas = $schema->getMetadataFactory()->getAllMetadata();
        $sqls = $schema->getUpdateSchemaSql($metadatas, false);
        
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
        $sqls = $schema->getUpdateSchemaSql($metadatas, true);
        
        return $sqls;
    }


    private function completeConfigInit()
    {
        $overrideSection = $this->_config->getSection('override_site');

        if (null !== $overrideSection) {
            $site = $this->getApplication()->getSite();
            if (null !== $site && true === isset($overrideSection[$site->getUid()])) {
                $siteConfig = $overrideSection[$site->getUid()];
                foreach ($siteConfig as $section => $datas) {
                    $this->_config->setSection($section, Arrays::array_merge_assoc_recursive(
                                    $this->_config->getSection($section), $siteConfig[$section]
                            ), true);
                }
            }
        }

        $this->isConfigFullyInit = true;
    }

    private function _initRouting()
    {
        $routing = $this->getConfig()->getRoutingConfig();
        if (is_null($routing)) {
            $this->_routing = false;
        }

        $this->_routing = new Routing($this->_application);
        $this->_routing->addBundleRouting($this);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return BBApplication
     */
    public function getApplication()
    {
        return $this->_application;
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
        return $this->_basedir;
    }

    /**
     * @codeCoverageIgnore
     * @return path
     */
    public function getResourcesDir()
    {
        //return $this->getBaseDir() . DIRECTORY_SEPARATOR . 'Ressources';

        $resources_dir = $this->getBaseDir() . DIRECTORY_SEPARATOR . 'Ressources';
        if ('test' === $this->_application->getContext()) {
            $test_dir = $this->getBaseDir() . DIRECTORY_SEPARATOR . 'Test' . DIRECTORY_SEPARATOR . 'Ressources';
            if (true === file_exists($test_dir)) {
                $resources_dir = $test_dir;
            }
        }

        return $resources_dir;
    }

    /**
     * @codeCoverageIgnore
     * @return integer
     */
    public function getId()
    {
        return $this->_id;
    }

    public function getRouting()
    {
        if (NULL === $this->_routing)
            $this->_initRouting();

        return $this->_routing;
    }

    /**
     * Returns the current request
     * @access public
     * @return Request
     */
    public function getRequest()
    {
        if (NULL === $this->_request)
            $this->_request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

        return $this->_request;
    }

    public function getConfig($configdir = null)
    {
        if (null === $this->_config) {
            $this->_initConfig($configdir);
        }

        if (
                true === $this->manageMultisiteConfig &&
                false === $this->isConfigFullyInit &&
                null !== $this->getApplication()->getSite()
        ) {
            $this->completeConfigInit();
        }

        return $this->_config;
    }

    /**
     * Do save of bundle new config, allow and manage multisite config
     */
    public function saveConfig()
    {
        if (false === $this->manageMultisiteConfig) {
            $this->doSaveConfig($this->_config->getAllSections());
            return;
        }

        $wipConfig = $this->_config->getAllSections();
        $updatedSections = Arrays::array_diff_assoc_recursive($wipConfig, $this->configDefaultSections);

        if (0 < count($updatedSections)) {
            $overrideSection = array();
            if (true === isset($this->configDefaultSections['override_site'])) {
                $overrideSection = $this->configDefaultSections['override_site'];
            }

            $overrideSection[$this->getApplication()->getSite()->getUid()] = $updatedSections;
            $this->configDefaultSections['override_site'] = $overrideSection;

            $this->doSaveConfig($this->configDefaultSections);
        } else {
            $registry = $this->_getRegistryConfig();
            if ($this->getApplication()->getEntityManager()->contains($registry)) {
                $this->getApplication()->getEntityManager()->remove($registry);
                $this->getApplication()->getEntityManager()->flush($registry);
            }
        }
    }

    /**
     * Returns the registry entry for bundle'x config storing
     * @return \BackBuilder\Bundle\Registry
     */
    private function _getRegistryConfig()
    {
        $registry = null;

        try {
            $registry = $this->getApplication()
                    ->getEntityManager()
                    ->getRepository('BackBuilder\Bundle\Registry')
                    ->findOneBy(array('key' => $this->getId(), 'scope' => 'BUNDLE.CONFIG'));
        } catch (\Exception $e) {
            if (true === $this->getApplication()->isStarted()) {
                $this->warning('Enable to load registry table');
            }
        }

        if (null === $registry) {
            $registry = new Registry();
            $registry->setKey($this->getId())
                    ->setScope('BUNDLE.CONFIG');
        }

        return $registry;
    }

    /**
     * Puts new settings into config files
     * 
     * @param  array  $config 
     */
    private function doSaveConfig(array $config)
    {
        $registry = $this->_getRegistryConfig();
        $registry->setValue(serialize($config));

        if (false === $this->getApplication()->getEntityManager()->contains($registry)) {
            $this->getApplication()->getEntityManager()->persist($registry);
        }

        $this->getApplication()->getEntityManager()->flush($registry);
    }

    public function getProperty($key = null)
    {
        if (NULL === $this->_properties) {
            $this->_properties = $this->getConfig()->getSection('bundle');
            if (NULL === $this->_properties)
                $this->_properties = array();
        }

        if (NULL === $key)
            return $this->_properties;

        if (array_key_exists($key, $this->_properties))
            return $this->_properties[$key];

        return NULL;
    }

    public function setLogger(Logger $logger)
    {
        $this->_logger = $logger;
        return $this;
    }

    public function serialize()
    {
        $obj = new \stdClass();
        $obj->id = $this->getId();

        foreach ($this->getProperty() as $key => $value)
            $obj->$key = $value;

        return json_encode($obj);
    }

    public function unserialize($serialized)
    {
        
    }

    abstract function init();

    abstract function start();

    abstract function stop();

    /*     * **************************************************************** */
    /*                                                                        */
    /*               Implementation of IObjectIdentifiable                    */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Returns a unique identifier for this domain object.
     * @return string
     * @see \BackBuilder\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function getObjectIdentifier()
    {
        return $this->getType() . '(' . $this->getIdentifier() . ')';
    }

    /**
     * Returns the unique identifier for this object.
     * @return string
     * @see \BackBuilder\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function getIdentifier()
    {
        return $this->getId();
    }

    /**
     * Returns the PHP class name of the object.
     * @return string
     * @see \BackBuilder\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function getType()
    {
        return ClassUtils::getRealClass($this);
    }

    /**
     * Checks for an explicit objects equality.
     * @param \BackBuilder\Security\Acl\Domain\IObjectIdentifiable $identity
     * @return Boolean
     * @see \BackBuilder\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function equals(IObjectIdentifiable $identity)
    {
        return ($this->getType() === $identity->getType() && $this->getIdentifier() === $identity->getIdentifier());
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
