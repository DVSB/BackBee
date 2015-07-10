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

namespace BackBee\Config;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use BackBee\Cache\CacheInterface;
use BackBee\Config\Exception\InvalidConfigException;
use BackBee\DependencyInjection\Container;
use BackBee\DependencyInjection\DispatchTagEventInterface;
use BackBee\DependencyInjection\Dumper\DumpableServiceInterface;
use BackBee\Utils\File\File;

/**
 * A set of configuration parameters store in a yaml file
 * The parameters had to be filtered by section
 * Note that parameters and services will be set only if setContainer() is called.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>, e.chau <eric.chau@lp-digital.fr>
 */
class Config implements DispatchTagEventInterface, DumpableServiceInterface
{
    /**
     * Config proxy classname.
     *
     * @var string
     */
    const CONFIG_PROXY_CLASSNAME = 'BackBee\Config\ConfigProxy';

    /**
     * Default config file to look for.
     *
     * @var string
     */
    const CONFIG_FILE = 'config';

    /**
     * System extension config file.
     *
     * @var string
     */
    const EXTENSION = 'yml';

    /**
     * The base directory to looking for configuration files.
     *
     * @var string
     */
    protected $basedir;

    /**
     * The extracted configuration parameters from the config file.
     *
     * @var array
     */
    protected $raw_parameters;

    /**
     * The already compiled parameters.
     *
     * @var array
     */
    protected $parameters;

    /**
     * The optional cache system.
     *
     * @var \BackBee\Cache\CacheInterface
     */
    protected $cache;

    /**
     * The service container.
     *
     * @var \BackBee\DependencyInjection\Container
     */
    protected $container;

    /**
     * Application's environment.
     *
     * @var string
     */
    protected $environment = \BackBee\ApplicationInterface::DEFAULT_ENVIRONMENT;

    /**
     * Is debug mode enabled.
     *
     * @var boolean
     */
    protected $debug = false;

    /**
     * Debug info.
     *
     * Only populated in dev environment
     *
     * @var array
     */
    protected $debug_data = array();

    /**
     * list of yaml filename we don't want to parse and load.
     *
     * @var array
     */
    protected $yml_names_to_ignore;

    /**
     * represents if current service has been already restored or not.
     *
     * @var boolean
     */
    protected $is_restored;

    /**
     * Class constructor.
     *
     * @param string                                 $basedir       The base directory in which look for config files
     * @param \BackBee\Cache\CacheInterface          $cache         Optional cache system
     * @param \BackBee\DependencyInjection\Container $container     The BackBee Container
     * @param boolean                                $debug         The debug mode
     * @param array                                  $yml_to_ignore List of yaml filename to ignore form loading/parsing process
     */
    public function __construct($basedir, CacheInterface $cache = null, Container $container = null, $debug = false, array $yml_to_ignore = array())
    {
        $this->basedir = $basedir;
        $this->raw_parameters = array();
        $this->cache = $cache;
        $this->debug = $debug;
        $this->yml_names_to_ignore = $yml_to_ignore;
        $this->is_restored = false;

        $this->setContainer($container)->extend();
    }

    /**
     * Magic function to get configuration section
     * The called method has to match the pattern getSectionConfig()
     * for example getDoctrineConfig() aliases getSection('doctrine').
     *
     * @access public
     *
     * @param string $name      The name of the called method
     * @param array  $arguments The arguments passed to the called method
     *
     * @return array The configuration section if exists NULL else
     */
    public function __call($name, $arguments)
    {
        $result = null;
        if (1 === preg_match('/get([a-z]+)config/i', strtolower($name), $sections)) {
            $section = $this->getSection($sections[1]);

            if (0 === count($arguments)) {
                $result = $section;
            } elseif (true === array_key_exists($arguments[0], $section)) {
                $result = $section[$arguments[0]];
            }
        }

        return $result;
    }

    /**
     * Set the service container to be able to parse parameter and service in config
     * Resets the compiled parameters array.
     *
     * @param \BackBee\DependencyInjection\Container $container
     *
     * @return \BackBee\Config\Config
     */
    public function setContainer(Container $container = null)
    {
        $this->container = $container;
        $this->parameters = array();

        return $this;
    }

    /**
     * Set the cache used for the configuration
     *
     * @param \BackBee\Cache\CacheInterface $cache
     *
     * @return \BackBee\Config\Config
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Get debug info.
     *
     * Populated only in dev env
     *
     * @return array
     */
    public function getDebugData()
    {
        return $this->debug_data;
    }

    /**
     * Add more yaml filename to ignore when we will try to find every yaml files of a directory.
     *
     * @param string|array $filename yaml filename(s) to ignore
     */
    public function addYamlFilenameToIgnore($filename)
    {
        $this->yml_names_to_ignore = array_unique(array_merge($this->yml_names_to_ignore, (array) $filename));
    }

    /**
     * Returns, if exists, the raw parameter section, null otherwise.
     *
     * @param string $section
     *
     * @return mixed|null
     */
    public function getRawSection($section = null)
    {
        if (null === $section) {
            return $this->raw_parameters;
        } elseif (array_key_exists($section, $this->raw_parameters)) {
            return $this->raw_parameters[$section];
        }

        return;
    }

    /**
     * Returns all raw paramter sections.
     *
     * @return array
     */
    public function getAllRawSections()
    {
        return $this->getRawSection();
    }

    /**
     * Returns, if exists, the parameter section.
     *
     * @param string $section
     *
     * @return array|NULL
     */
    public function getSection($section = null)
    {
        if (null === $this->container) {
            return $this->getRawSection($section);
        }

        return $this->compileParameters($section);
    }

    /**
     * Returns all sections.
     *
     * @return array
     */
    public function getAllSections()
    {
        return $this->getSection();
    }

    /**
     * Delete section by name and its parameters.
     *
     * @param string $section the name of the section you want to delete
     *
     * @return self
     */
    public function deleteSection($section)
    {
        unset($this->raw_parameters[$section]);
        unset($this->parameters[$section]);

        return $this;
    }

    /**
     * Delete every sections of current Config.
     *
     * @return self
     */
    public function deleteAllSections()
    {
        $this->raw_parameters = array();
        $this->parameters = array();

        return $this;
    }

    /**
     * Set environment context.
     *
     * @param string $env
     *
     * @return self
     */
    public function setEnvironment($env)
    {
        $this->environment = $env;

        return $this;
    }

    /**
     * Set debug mode.
     *
     * @param boolean $debug
     *
     * @return self
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Checks if the key exists in the parameter section.
     *
     * @param string $section
     * @param string $key
     *
     * @return boolean
     */
    public function sectionHasKey($section, $key)
    {
        return (isset($this->raw_parameters[$section])
            && is_array($this->raw_parameters[$section])
            && array_key_exists($key, $this->raw_parameters[$section])
        );
    }

    /**
     * Sets a parameter section.
     *
     * @param string  $section
     * @param array   $config
     * @param boolean $overwrite
     *
     * @return \BackBee\Config\Config The current config object
     */
    public function setSection($section, array $config, $overwrite = false)
    {
        if (false === $overwrite && array_key_exists($section, $this->raw_parameters)) {
            $this->raw_parameters[$section] = array_replace_recursive($this->raw_parameters[$section], $config);
        } else {
            $this->raw_parameters[$section] = $config;
        }

        if (array_key_exists($section, $this->parameters)) {
            unset($this->parameters[$section]);
        }

        return $this;
    }

    /**
     * Extends the current instance with a new base directory.
     *
     * @param string $basedir Optional base directory
     */
    public function extend($basedir = null, $overwrite = false)
    {
        if (null === $basedir) {
            $basedir = $this->basedir;
        }

        $basedir = File::realpath($basedir);

        if (false === $this->loadFromCache($basedir)) {
            $this->loadFromBaseDir($basedir, $overwrite);
            $this->saveToCache($basedir);
        }

        if (
            !empty($this->environment)
            && false === strpos($this->environment, $basedir)
            && is_dir($basedir.DIRECTORY_SEPARATOR.$this->environment)
        ) {
            $this->extend($basedir.DIRECTORY_SEPARATOR.$this->environment, $overwrite);
        }

        return $this;
    }

    /**
     * Returns setted base directory.
     *
     * @return string absolute path to current Config base directory
     */
    public function getBaseDir()
    {
        return $this->basedir;
    }

    /**
     * @see BackBee\DependencyInjection\DispatchTagEventInterface::needDispatchEvent
     */
    public function needDispatchEvent()
    {
        return true;
    }

    /**
     * Returns the namespace of the class proxy to use or null if no proxy is required.
     *
     * @return string|null the namespace of the class proxy to use on restore or null if no proxy required
     */
    public function getClassProxy()
    {
        return self::CONFIG_PROXY_CLASSNAME;
    }

    /**
     * Dumps current service state so we can restore it later by calling DumpableServiceInterface::restore()
     * with the dump array produced by this method.
     *
     * @return array contains every datas required by this service to be restored at the same state
     */
    public function dump(array $options = array())
    {
        return array(
            'basedir'             => $this->basedir,
            'raw_parameters'      => $this->raw_parameters,
            'environment'         => $this->environment,
            'debug'               => $this->debug,
            'yml_names_to_ignore' => $this->yml_names_to_ignore,
            'has_cache'           => null !== $this->cache,
            'has_container'       => null !== $this->container,
        );
    }

    /**
     * @return boolean true if current service is already restored, otherwise false
     */
    public function isRestored()
    {
        return $this->is_restored;
    }

    /**
     * If a cache system is defined, try to load a cache for the current basedir.
     *
     * @param string $basedir The base directory
     *
     * @return boolean Returns TRUE if a valid cache has been found, FALSE otherwise
     */
    private function loadFromCache($basedir)
    {
        if (true === $this->debug) {
            return false;
        }

        if (null === $this->cache) {
            return false;
        }

        $cached_parameters = $this->cache->load($this->getCacheId($basedir), false, $this->getCacheExpire($basedir));
        if (false === $cached_parameters) {
            return false;
        }

        $parameters = @\unserialize($cached_parameters);
        if (!is_array($parameters)) {
            return false;
        }

        foreach ($parameters as $section => $data) {
            $this->setSection($section, $data, true);
        }

        return true;
    }

    /**
     * Saves the parameters in cache system if defined.
     *
     * @param string $basedir The base directory
     *
     * @return boolean Returns TRUE if a valid cache has been saved, FALSE otherwise
     */
    private function saveToCache($basedir)
    {
        if (true === $this->debug) {
            return false;
        }

        if (null !== $this->cache) {
            return $this->cache->save($this->getCacheId($basedir), serialize($this->raw_parameters), null, null, true);
        }

        return false;
    }

    /**
     * Returns a cache expiration date time (the newer modification date of files).
     *
     * @param string $basedir The base directory
     *
     * @return \DateTime
     */
    private function getCacheExpire($basedir)
    {
        $expire = 0;

        foreach ($this->getYmlFiles($basedir) as $file) {
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
     * Returns a cache id for the current instance.
     *
     * @param string $basedir The base directory
     *
     * @return string
     */
    private function getCacheId($basedir)
    {
        return md5('config-'.$basedir.$this->environment);
    }

    /**
     * Returns an array of YAML files in the directory.
     *
     * @param string $basedir The base directory
     *
     * @return array
     *
     * @throws \BackBee\Config\Exception\InvalidBaseDirException Occurs if the base directory cannont be read
     */
    private function getYmlFiles($basedir)
    {
        $ymlFiles = File::getFilesByExtension($basedir, self::EXTENSION);

        $defaultFile = $basedir.DIRECTORY_SEPARATOR.self::CONFIG_FILE.'.'.self::EXTENSION;

        if (is_file($defaultFile) && 1 < count($ymlFiles)) {
            // Ensure that config.yml is the first one
            $ymlFiles = array_diff($ymlFiles, array($defaultFile));
            array_unshift($ymlFiles, $defaultFile);
        }

        foreach ($ymlFiles as &$file) {
            $name = basename($file);
            if (in_array(substr($name, 0, strrpos($name, '.')), $this->yml_names_to_ignore)) {
                $file = null;
            }
        }

        return array_filter($ymlFiles);
    }

    /**
     * Loads the config files from the base directory.
     *
     * @param string $basedir The base directory
     *
     * @throws \BackBee\Config\Exception\InvalidBaseDirException Occurs if the base directory can't be read
     */
    private function loadFromBaseDir($basedir, $overwrite = false)
    {
        foreach ($this->getYmlFiles($basedir) as $filename) {
            $this->loadFromFile($filename, $overwrite);
        }
    }

    /**
     * Try to parse a yaml config file.
     *
     * @param string $filename
     *
     * @throws \BackBee\Config\Exception\InvalidConfigException Occurs when the file can't be parsed
     */
    private function loadFromFile($filename, $overwrite = false)
    {
        try {
            $yamlDatas = Yaml::parse(file_get_contents($filename));

            if (is_array($yamlDatas)) {
                if (true === $this->debug) {
                    $this->debug_data[$filename] = $yamlDatas;
                }

                if (self::CONFIG_FILE.'.'.self::EXTENSION === basename($filename) ||
                    self::CONFIG_FILE.'.'.$this->environment.'.'.self::EXTENSION === basename($filename)) {
                    foreach ($yamlDatas as $component => $config) {
                        if (!is_array($config)) {
                            $this->container->get('logger')->error('Bad configuration, array expected, given : '.$config);
                        }
                        $this->setSection($component, $config, $overwrite);
                    }
                } else {
                    $this->setSection(basename($filename, '.'.self::EXTENSION), $yamlDatas, $overwrite);
                }
            }
        } catch (ParseException $e) {
            throw new InvalidConfigException($e->getMessage(), null, $e, $e->getParsedFile(), $e->getParsedLine());
        }
    }

    /**
     * Replace services and container parameters keys by their values for the whole config.
     *
     * @return array
     */
    private function compileAllParameters()
    {
        foreach (array_keys($this->raw_parameters) as $section) {
            $this->parameters[$section] = $this->compileParameters($section);
        }

        return $this->parameters;
    }

    /**
     * Replace services and container parameters keys by their values for the provided section.
     *
     * @param string|null $section The selected configuration section, can be null
     *
     * @return array
     */
    private function compileParameters($section = null)
    {
        if (null === $section) {
            return $this->compileAllParameters();
        }

        if (!array_key_exists($section, $this->raw_parameters)) {
            return;
        }

        if (!array_key_exists($section, $this->parameters)) {
            $value = $this->raw_parameters[$section];
            if (is_array($value)) {
                array_walk_recursive($value, array($this->container, 'getContainerValues'));
            } else {
                $this->container->getContainerValues($value);
            }
            $this->parameters[$section] = $value;
        }

        return $this->parameters[$section];
    }
}
