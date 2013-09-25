<?php
namespace BackBuilder\Config;

use BackBuilder\Config\Exception\ConfigException;

use Symfony\Component\Yaml\Exception\ParseException,
    Symfony\Component\Yaml\Yaml;

/**
 * A set of configuration parameters store in a yaml file
 * The parameters had to be filtered by section
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp system
 * @author      c.rouillon
 */
class Config {
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
     * Magic function to get configuration section
     * The called method has to match the pattern getSectionConfig()
     * for example getDoctrineConfig() aliases getSection('doctrine')
     *
     * @access public
     * @param string $name The name of the called method
     * @param array $arguments The arguments passed to the called method
     * @return array The configuration section if exists NULL else
     */
    public function __call($name, $arguments) {
        $sections = array();
        $is_match = preg_match('/get([a-z]+)config/i', strtolower($name), $sections);
        if ($is_match) {
            $section = $this->getSection($sections[1]);

            if (key($arguments) !== null && array_key_exists($arguments[0], $section))
                return $section[$arguments[0]];

            return $section;
        } else {
            return NULL;
        }
    }
    
    /**
     * Class constructor
     *
     * @access public
     * @param string $filename the file path to the config file to parse
     */
    public function __construct($basedir) {
        if (!is_readable($basedir))
            throw new ConfigException(sprintf('Cannot read the directory %s', $basedir));
        
        $this->_basedir = realpath($basedir).DIRECTORY_SEPARATOR;
        $this->_parameters = array();
        
        $pattern = $this->_basedir.'{*,*'.DIRECTORY_SEPARATOR.'*}.[yY][mM][lL]';
        foreach(glob($pattern, GLOB_BRACE) as $filename)
            $this->_loadFromFile($filename);
    }
    
    /**
     * Try to parse a yaml config file
     *
     * @access private
     * @param string $filename
     * @throws ConfigException Occur when the file can't be parse
     */
    private function _loadFromFile($filename, $overwrite = false) {
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
            throw new ConfigException($e->getMessage(), ConfigException::UNABLE_TO_PARSE, $e, $e->getParsedFile(), $e->getParsedLine());
        }
    }
    
    /**
     * Return, if exists, the parameter section
     *
     * @access public
     * @param string $section
     * @return array|NULL
     */
    public function getSection($section) {
        return (isset($this->_parameters[$section])) ? $this->_parameters[$section] : NULL;
    }
    
    /**
     * Set a parameter section
     *
     * @access public
     * @param string $section
     * @param array $config
     * @param boolean $overwrite
     * @return Config The current config object
     */
    public function setSection($section, array $config, $overwrite = false) {
        if (false === $overwrite && array_key_exists($section, $this->_parameters))
            $this->_parameters[$section] = array_merge_recursive($this->_parameters[$section], $config);
        else
            $this->_parameters[$section] = $config;
        return $this;
    }
    
    public function extend($basedir)
    {
        if (!is_readable($basedir))
            throw new ConfigException(sprintf('Cannot read the directory %s', $basedir));
        
        $basedir = realpath($basedir).DIRECTORY_SEPARATOR;
        
        $pattern = $basedir.'{*,*'.DIRECTORY_SEPARATOR.'*}.[yY][mM][lL]';
        foreach(glob($pattern, GLOB_BRACE) as $filename)
            $this->_loadFromFile($filename, true);        
    }
}