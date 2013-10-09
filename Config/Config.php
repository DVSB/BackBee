<?php

namespace BackBuilder\Config;

use BackBuilder\Cache\ACache;
use Symfony\Component\Yaml\Exception\ParseException,
    Symfony\Component\Yaml\Yaml;

/**
 * A set of configuration parameters store in a yaml file
 * The parameters had to be filtered by section
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class Config
{

    /**
     * Default config file to look for
     * @var string
     */
    const CONFIG_FILE = 'config.yml';

    /**
     * The base directory to looking for configuration files
     * @var string
     */
    private $_basedir;

    /**
     * The extracted configuration parameters from the config file
     * @var array
     */
    private $_parameters;

    /**
     * The optional cache system
     * @var \BackBuilder\Cache\ACache
     */
    private $_cache;

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
     */
    public function __construct($basedir, ACache $cache = null)
    {
        $this->_basedir = $basedir;
        $this->_parameters = array();
        $this->_cache = $cache;

        $this->extend();
    }

    /**
     * If a cache system is defined, try to load a cache for the current instance
     * @param string $basedir The base directory
     * @return boolean Returns TRUE if a valid cache has been found, FALSE otherwise
     */
    private function _loadFromCache($basedir)
    {
        if (null !== $this->_cache) {
            if (false !== $parameters = $this->_cache->load($this->_getCacheId($basedir), false, $this->_getCacheExpire($basedir))) {
                $parameters = @unserialize($parameters);
                if (true === is_array($parameters)) {
                    foreach ($parameters as $section => $data) {
                        $this->setSection($section, $data, true);
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Saves the parameters in cache system if defined
     * @param string $basedir The base directory
     * @return boolean Returns TRUE if a valid cache has been saved, FALSE otherwise
     */
    private function _saveToCache($basedir)
    {
        if (null !== $this->_cache) {
            return $this->_cache->save($this->_getCacheId($basedir), serialize($this->_parameters));
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
        foreach($this->_getYmlFiles($basedir) as $file) {
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
        return md5('config-' . $basedir);
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
        if (false === is_readable($basedir)) {
            throw new Exception\InvalidBaseDirException(sprintf('Cannot read the directory %s', $basedir));
        }

        $pattern = $basedir . '{*,*' . DIRECTORY_SEPARATOR . '*}.[yY][mM][lL]';
        return glob($pattern, GLOB_BRACE);
    }

    /**
     * Loads the config files from the base directory
     * @param string $basedir The base directory
     * @throws \BackBuilder\Config\Exception\InvalidBaseDirException Occurs if the base directory cannont be read
     */
    private function _loadFromBaseDir($basedir)
    {
        foreach ($this->_getYmlFiles($basedir) as $filename) {
            $this->_loadFromFile($filename);
        }
    }

    /**
     * Try to parse a yaml config file
     * @param string $filename
     * @throws \BackBuilder\Config\Exception\InvalidConfigException Occurs when the file can't be parse
     */
    private function _loadFromFile($filename, $overwrite = false)
    {
        try {
            $yamlDatas = Yaml::parse($filename);
            if (is_array($yamlDatas)) {
                if (self::CONFIG_FILE === basename($filename)) {
                    foreach ($yamlDatas as $component => $config) {
                        $this->setSection($component, $config, $overwrite);
                    }
                } else {
                    $this->setSection(substr(basename($filename), 0, -4), $yamlDatas, $overwrite);
                }
            }
        } catch (ParseException $e) {
            throw new Exception\InvalidConfigException($e->getMessage(), null, $e, $e->getParsedFile(), $e->getParsedLine());
        }
    }

    /**
     * Returns, if exists, the parameter section
     * @param string $section
     * @return array|NULL
     */
    public function getSection($section = null)
    {
        if (null === $section) {
            return $this->_parameters;
        }

        return (isset($this->_parameters[$section])) ? $this->_parameters[$section] : null;
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
        if (false === $overwrite && array_key_exists($section, $this->_parameters)) {
            $this->_parameters[$section] = array_replace_recursive($this->_parameters[$section], $config);
        } else {
            $this->_parameters[$section] = $config;
        }

        return $this;
    }

    /**
     * Extends the current instance with a new base directory
     * @param string $basedir Optional base directory
     */
    public function extend($basedir = null)
    {
        if (null === $basedir) {
            $basedir = $this->_basedir;
        }

        $basedir = realpath($basedir) . DIRECTORY_SEPARATOR;

        if (false === $this->_loadFromCache($basedir)) {
            $this->_loadFromBaseDir($basedir);
            $this->_saveToCache($basedir);
        }
    }

}