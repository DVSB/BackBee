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
abstract class ABundle implements IObjectIdentifiable
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

    private $started;

    /**
     * @var array
     */
    private $config_default_sections;

    /**
     * @var boolean
     */
    private $manage_multisite_config = true;

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
        if (null !== $this->getLogger()) {
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

        if (null === $this->_logger) {
            $this->_logger = $this->_application->getLogging();
        }

        $this->started = false;
    }

    private function _initConfig($configdir = null)
    {
        $this->_config = $this->getApplication()->getContainer()->get(
            self::getBundleConfigServiceId($this->getBaseDir())
        );

        if (null === $this->_config) {
            $this->_config = self::initBundleConfig($this->getApplication(), $this->_basedir);
        }

        $all_sections = $this->_config->getAllRawSections();
        $this->config_default_sections = $all_sections;

        if (
            true === array_key_exists('bundle', $all_sections) &&
            true === array_key_exists('manage_multisite', $all_sections['bundle']) &&
            false === $all_sections['bundle']['manage_multisite']
        ) {
            $this->manage_multisite_config = false;
        }

        return $this;
    }

    /**
     * [getBundleConfigServiceId description]
     * @param  [type] $bundle_base_directory [description]
     * @return [type]                        [description]
     */
    public static function getBundleConfigServiceId($bundle_base_directory)
    {
        return strtolower(implode('.', array('bundle', basename($bundle_base_directory), 'config')));
    }

    /**
     * [initBundleConfig description]
     * @param  BBApplication $application           [description]
     * @param  [type]        $bundle_base_directory [description]
     * @return [type]                               [description]
     */
    public static function initBundleConfig(BBApplication $application, $bundle_base_directory)
    {
        $config_dir = implode(DIRECTORY_SEPARATOR, array($bundle_base_directory, 'Ressources'));
        $config = new Config($config_dir, $application->getBootstrapCache(), null, $application->isDebugMode());
        $config->setEnvironment($application->getEnvironment());
        $config->setContainer($application->getContainer());

        $id = basename($bundle_base_directory);
        self::overrideConfigWithEnvironment($config, $application, $id);

        self::overrideConfigWithRegistry($config, $application, $id);

        return $config;
    }

    /**
     * [overrideConfigWithEnvironment description]
     * @param  Config        $config      [description]
     * @param  BBApplication $application [description]
     * @param  [type]        $id          [description]
     * @return [type]                     [description]
     */
    private static function overrideConfigWithEnvironment(Config $config, BBApplication $application, $id)
    {
        if (BBApplication::DEFAULT_ENVIRONMENT !== $application->getEnvironment()) {
            $dir = implode(DIRECTORY_SEPARATOR, array(
                $application->getConfigDir(),
                $application->getEnvironment(),
                'bundle',
                $id
            ));

            if (true === is_dir($dir)) {
                $config->extend($dir, true);
            }
        }
    }

    /**
     * [overrideConfigWithRegistry description]
     * @param  Config        $config      [description]
     * @param  BBApplication $application [description]
     * @param  [type]        $id          [description]
     * @return [type]                     [description]
     */
    private static function overrideConfigWithRegistry(Config $config, BBApplication $application, $id)
    {
        $registry = self::_getRegistryConfig($application, $id);
        if (null !== $registry && null !== $serialized = $registry->getValue()) {
            $registry_config = @unserialize($serialized);

            if (true === is_array($registry_config)) {
                foreach ($registry_config as $section => $value) {
                    $config->setSection($section, $value, true);
                }
            }
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

    /**
     *
     * @param string|null $configdir
     * @return Config
     */
    public function getConfig($configdir = null)
    {
        if (null === $this->_config) {
            $this->_initConfig($configdir);
        }

        if (
                true === $this->manage_multisite_config &&
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
        if (false === $this->manage_multisite_config) {
            $this->doSaveConfig($this->_config->getAllSections());

            return;
        }

        $wipConfig = $this->_config->getAllSections();
        $updatedSections = Arrays::array_diff_assoc_recursive($wipConfig, $this->config_default_sections);

        if (0 < count($updatedSections)) {
            $overrideSection = array();
            if (true === isset($this->config_default_sections['override_site'])) {
                $overrideSection = $this->config_default_sections['override_site'];
            }

            $overrideSection[$this->getApplication()->getSite()->getUid()] = $updatedSections;
            $this->config_default_sections['override_site'] = $overrideSection;

            $this->doSaveConfig($this->config_default_sections);
        } else {
            $registry = self::_getRegistryConfig($this->getApplication(), $this->_id);
            if ($this->getApplication()->getEntityManager()->contains($registry)) {
                $this->getApplication()->getEntityManager()->remove($registry);
                $this->getApplication()->getEntityManager()->flush($registry);
            }
        }
    }

    /**
     * Returns the registry entry for bundle's config storing
     * @return \BackBuilder\Bundle\Registry
     */
    private static function _getRegistryConfig(BBApplication $application, $id)
    {
        $registry = null;
        $em = $application->getEntityManager();
        if(null !== $em) {
            try {
                $registry = $em->getRepository('BackBuilder\Bundle\Registry')
                    ->findRegistryEntityByIdAndScope($id, 'BUNDLE.CONFIG');
            } catch (\Exception $e) {
                if (true === $application->isStarted()) {
                    $application->warning('Unable to load registry table');
                }
            }

            if (null === $registry) {
                $registry = new Registry();
                $registry->setKey($id)
                         ->setScope('BUNDLE.CONFIG')
                ;
            }
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
        $registry = self::_getRegistryConfig($this->getApplication(), $this->_id);
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

    abstract function start();

    abstract function stop();

    /**
     * [isStarted description]
     * @return boolean [description]
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * [started description]
     * @return [type] [description]
     */
    public function started()
    {
        $this->started = true;
    }


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
