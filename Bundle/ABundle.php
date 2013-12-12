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
    BackBuilder\Util\Arrays;

use Symfony\Component\Security\Core\Util\ClassUtils,
    Symfony\Component\Yaml\Yaml;

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

        // To do : check for a specific EntityManager
        $this->_em = $this->_application->getEntityManager();

        $r = new \ReflectionObject($this);
        $this->_basedir = dirname($r->getFileName());
        $this->_id = basename($this->_basedir);

        $this->_logger = $logger;
        if (NULL === $this->_logger)
            $this->_logger = $this->_application->getLogging();
    }

    private function _initConfig($configdir = null)
    {
        if (is_null($configdir)) {
            $configdir = $this->getResourcesDir();
        }

        $this->_config = new Config($configdir, $this->getApplication()->getBootstrapCache());
        $allSections = $this->_config->getAllSections();
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

    private function completeConfigInit()
    {
        $overrideSection = $this->_config->getSection('override_site');

        if (null !== $overrideSection) {
            $site = $this->getApplication()->getSite();
            if (null !== $site && true === isset($overrideSection[$site->getUid()])) {
                $siteConfig = $overrideSection[$site->getUid()];
                foreach ($siteConfig as $section => $datas) {
                    $this->_config->setSection($section, Arrays::array_merge_assoc_recursive(
                        $this->_config->getSection($section),
                        $siteConfig[$section]
                    ), true);
                }
            }
        }

        $this->isConfigFullyInit = true;
    }

    private function _initRouting()
    {
        $routing = $this->getConfig()->getRoutingConfig();
        if (is_null($routing))
            $this->_routing = false;

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
     * @codeCoverageIgnore
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
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
        return $this->getBaseDir() . DIRECTORY_SEPARATOR . 'Ressources';
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

    public function getConfig()
    {
        if (null === $this->_config) {
            $this->_initConfig();
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
        }
    }

    private function doSaveConfig(array $config)
    {
        file_put_contents(
            $this->getBaseDir() . DIRECTORY_SEPARATOR . 'Ressources' . DIRECTORY_SEPARATOR . 'config.yml', 
            Yaml::dump($config)
        );
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
        return ($this->getType() === $identity->getType()
                && $this->getIdentifier() === $identity->getIdentifier());
    }
}
