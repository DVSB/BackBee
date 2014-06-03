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

namespace BackBuilder\Config;

use BackBuilder\Cache\ACache,
    BackBuilder\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Exception\ParseException,
    Symfony\Component\Yaml\Yaml;

/**
 * A set of configuration parameters store in a yaml file
 * The parameters had to be filtered by section
 * Note that parameters and services will be set only if setContainer() is called
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Config
{

    /**
     * Default config file to look for
     * @var string
     */
    const CONFIG_FILE = 'config';
    
    /**
     * System events config file to look for
     * @var string
     */
    const EVENTS_FILE = 'events';

    /**
     * System extention config file
     * @var string
     */
    const EXTENTION = 'yml';

    /**
     * The base directory to looking for configuration files
     * @var string
     */
    private $_basedir;

    /**
     * The extracted configuration parameters from the config file
     * @var array
     */
    private $_raw_parameters;

    /**
     * The already compiled parameters
     * @var array
     */
    private $_parameters;

    /**
     * The optional cache system
     * @var \BackBuilder\Cache\ACache
     */
    private $_cache;

    /**
     * The service container
     * @var \BackBuilder\DependencyInjection\ContainerBuilder
     */
    private $_container;

    private $_environment = 'production';
    
    /**
     * Is debug mode enabled
     * 
     * @var boolean
     */
    private $_debug = false;
    
    /**
     * Debug info
     * 
     * Only populated in dev environment
     * 
     * @var array
     */
    protected $_debugData = array();

    /**
     * Magic function to get configuration section
     * The called method has to match the pattern getSectionConfig()
     * for example getDoctrineConfig() aliases getSection('doctrine')
     *
     * @access public
     * @param string $name The name of the called method
     * @param array $arguments The arguments passed to the called method
     * @return array The configuration section if exists NULL else
     */
    public function __call($name, $arguments)
    {
        $sections = array();

        $is_match = preg_match('/get([a-z]+)config/i', strtolower($name), $sections);
        if ($is_match) {
            $section = $this->getSection($sections[1]);

            if (key($arguments) !== null && array_key_exists($arguments[0], $section)) {
                return $section[$arguments[0]];
            }

            return $section;
        }

        return null;
    }

    /**
     * Class constructor
     * @param string $basedir The base directory in which look for config files
     * @param \BackBuilder\Cache\ACache $cache Optional cache system
     * @param \BackBuilder\DependencyInjection\ContainerBuilder $container
     */
    public function __construct($basedir, ACache $cache = null, ContainerBuilder $container = null, $debug = false)
    {
        $this->_basedir = $basedir;
        $this->_raw_parameters = array();
        $this->_cache = $cache;
        $this->_debug = $debug;
        $this->setContainer($container)->extend();
    }
    

    /**
     * Set the service container to be able to parse parameter and service in config
     * Resets the compiled parameters array
     * @param \BackBuilder\DependencyInjection\ContainerBuilder $container
     * @return \BackBuilder\Config\Config
     */
    public function setContainer(ContainerBuilder $container = null)
    {
        $this->_container = $container;
        $this->_parameters = array();
        return $this;
    }
    
    /**
     * Get debug info 
     * 
     * Populated only in dev env
     * 
     * @return array
     */
    public function getDebugData()
    {
        return $this->_debugData;
    }

    /**
     * If a cache system is defined, try to load a cache for the current basedir
     * @param string $basedir The base directory
     * @return boolean Returns TRUE if a valid cache has been found, FALSE otherwise
     */
    private function _loadFromCache($basedir)
    {
        if(true === $this->_debug) {
            return false;
        }

        if (null === $this->_cache) {
            return false;
        }

        $cached_parameters = $this->_cache->load($this->_getCacheId($basedir), false, $this->_getCacheExpire($basedir));
        if (false === $cached_parameters) {
            return false;
        }
        
        $parameters = @\unserialize($cached_parameters);
        if (false === is_array($parameters)) {
            return false;
        }

        foreach ($parameters as $section => $data) {
            $this->setSection($section, $data, true);
        }

        return true;
    }

    /**
     * Saves the parameters in cache system if defined
     * @param string $basedir The base directory
     * @return boolean Returns TRUE if a valid cache has been saved, FALSE otherwise
     */
    private function _saveToCache($basedir)
    {
        if(true === $this->_debug) {
            return false;
        }
        
        if (null !== $this->_cache) {
            return $this->_cache->save($this->_getCacheId($basedir), serialize($this->_raw_parameters));
        }

        return false;
    }

    /**
     * Returns a cache expiration date time (the newer modification date of files)
     * @param string $basedir The base directory
     * @return \DateTime
     */
    private function _getCacheExpire($basedir)
    {
        $expire = 0;

        foreach ($this->_getYmlFiles($basedir) as $file) {
            $stat = @stat($file);
            if ($expire < $stat['mtime']) {
                $expire = $stat['mtime'];
            }
        }

        $date = new \DateTime();
        if (0 !== $expire) {
            $date->setTimestamp($expire);
        }

        return $date;
    }

    /**
     * Returns a cache id for the current instance
     * @param string $basedir The base directory
     * @return string
     */
    private function _getCacheId($basedir)
    {
        return md5('config-' . $basedir . $this->_environment);
    }

    /**
     * Returns an array of YAML files in the directory
     * @param string $basedir
     * @param string $basedir The base directory
     * @return array
     * @throws \BackBuilder\Config\Exception\InvalidBaseDirException Occurs if the base directory cannont be read
     */
    private function _getYmlFiles($basedir)
    {
        $yml_files = \BackBuilder\Util\File::getFilesByExtension($basedir, self::EXTENTION);

        $default_file = $basedir . DIRECTORY_SEPARATOR . self::CONFIG_FILE . '.' . self::EXTENTION;

        if (true === file_exists($default_file) && 1 < count($yml_files)) {
            // Ensure that config.yml is the first one
            $yml_files = array_diff($yml_files, array($default_file));
            array_unshift($yml_files, $default_file);
        }

        return $yml_files;
    }

    /**
     * Loads the config files from the base directory
     * @param string $basedir The base directory
     * @throws \BackBuilder\Config\Exception\InvalidBaseDirException Occurs if the base directory cannont be read
     */
    private function _loadFromBaseDir($basedir, $overwrite = false)
    {
        foreach ($this->_getYmlFiles($basedir) as $filename) {
            $this->_loadFromFile($filename, $overwrite);
        }
    }

    /**
     * Try to parse a yaml config file
     * @param string $filename
     * @throws \BackBuilder\Config\Exception\InvalidConfigException Occurs when the file can't be parsed
     */
    private function _loadFromFile($filename, $overwrite = false)
    {
        try {
            $yamlDatas = Yaml::parse($filename);

            if (is_array($yamlDatas)) {
                if(true === $this->_debug) {
                    $this->_debugData[$filename] = $yamlDatas;
                }
                
                if (self::CONFIG_FILE . '.' . self::EXTENTION === basename($filename) || 
                    self::CONFIG_FILE . '.' . $this->_environment . '.' . self::EXTENTION === basename($filename)) {

                    foreach ($yamlDatas as $component => $config) {
                        $this->setSection($component, $config, $overwrite);
                    }
                } else {
                    $this->setSection(basename($filename, '.' . self::EXTENTION), $yamlDatas, $overwrite);
                }
            }
        } catch (ParseException $e) {
            throw new Exception\InvalidConfigException($e->getMessage(), null, $e, $e->getParsedFile(), $e->getParsedLine());
        }
    }

    /**
     * Returns, if exists, the raw parameter section, NULL otherwise
     * @param string $section
     * @return mixed|NULL
     */
    public function getRawSection($section = null)
    {
        if (null === $section) {
            return $this->_raw_parameters;
        } elseif (true === array_key_exists($section, $this->_raw_parameters)) {
            return $this->_raw_parameters[$section];
        }

        return null;
    }

    /**
     * Returns all raw paramter sections
     * @return array
     */
    public function getAllRawSections()
    {
        return $this->getRawSection();
    }

    /**
     * Returns, if exists, the parameter section
     * @param string $section
     * @return array|NULL
     */
    public function getSection($section = null)
    {
        if (null === $this->_container) {
            return $this->getRawSection($section);
        }

        return $this->_compileParameters($section);
    }

    /**
     * Returns all sections
     * @return array
     */
    public function getAllSections()
    {
        return $this->getSection();
    }

    /**
     * Set environment context
     * @param string $env
     * @return self
     */
    public function setEnvironment($env)
    {
        $this->_environment = $env;
        return $this;
    }
    
    /**
     * Set debug mode
     * @param boolean $debug
     * @return self
     */
    public function setDebug($debug)
    {
        $this->_debug = $debug;
        return $this;
    }

    /**
     * Replace services and container parameters keys by their values for the whole config
     * @return array
     */
    private function _compileAllParameters()
    {
        foreach (array_keys($this->_raw_parameters) as $section) {
            $this->_parameters[$section] = $this->_compileParameters($section);
        }

        return $this->_parameters;
    }

    /**
     * Replace services and container parameters keys by their values for the provided section
     * @return array
     */
    private function _compileParameters($section = null)
    {
        if (null === $section) {
            return $this->_compileAllParameters();
        }

        if (false === array_key_exists($section, $this->_raw_parameters)) {
            return null;
        }

        if (false === array_key_exists($section, $this->_parameters)) {
            $value = $this->_raw_parameters[$section];
            if (true === is_array($value)) {
                array_walk_recursive($value, array($this->_container, 'getContainerValues'));
            } else {
                $this->_container->getContainerValues($value);
            }
            $this->_parameters[$section] = $value;
        }

        return $this->_parameters[$section];
    }

    /**
     * Checks if the key exists in the parameter section
     * @param string $section
     * @param string $key
     * @return boolean
     */
    public function sectionHasKey($section, $key)
    {
        if (isset($this->_raw_parameters[$section]) &&
                is_array($this->_raw_parameters[$section]) &&
                array_key_exists($key, $this->_raw_parameters[$section])) {
            return true;
        }

        return false;
    }

    /**
     * Sets a parameter section
     * @param string $section
     * @param array $config
     * @param boolean $overwrite
     * @return \BackBuilder\Config\Config The current config object
     */
    public function setSection($section, array $config, $overwrite = false)
    {
        if (false === $overwrite && array_key_exists($section, $this->_raw_parameters)) {
            $this->_raw_parameters[$section] = array_replace_recursive($this->_raw_parameters[$section], $config);
        } else {
            $this->_raw_parameters[$section] = $config;
        }

        if (true === array_key_exists($section, $this->_parameters)) {
            unset($this->_parameters[$section]);
        }

        return $this;
    }

    /**
     * Extends the current instance with a new base directory
     * @param string $basedir Optional base directory
     */
    public function extend($basedir = null, $overwrite = false)
    {
        if (null === $basedir) {
            $basedir = $this->_basedir;
        }

        $basedir = \BackBuilder\Util\File::realpath($basedir);

        if (false === $this->_loadFromCache($basedir)) {
            $this->_loadFromBaseDir($basedir, $overwrite);
            $this->_saveToCache($basedir);
        }

        if (!empty($this->_environment) &&
            false === strpos($this->_environment, $basedir) &&
            file_exists($basedir . DIRECTORY_SEPARATOR . $this->_environment)) {

            $this->extend($basedir . DIRECTORY_SEPARATOR . $this->_environment, $overwrite);
        }
        
        return $this;
    }

    /**
     * Returns setted base directory
     * @return string absolute path to current Config base directory
     */
    public function getBaseDir()
    {
        return $this->_basedir;
    }
}
