<?php
namespace BackBuilder\Importer;

use BackBuilder\BBApplication,
    BackBuilder\Config\Config;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Import
 * @copyright   Lp system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
abstract class AImportBundle
{
    protected $_dir;
    /**
     * @var BBApplication
     */
    protected $_config;
    /**
     * @var Config
     */
    protected $_relations;
    /**
     * @var type
     */
    protected $_application;

    public function __construct(BBApplication $application)
    {
        $this->_dir = __DIR__;
        $this->_application = $application;
        $this->_config = new Config($this->_dir);
        $this->_relations = $this->_config->getSection('relations');
        if (0 == count($this->_relations)) return false;
        foreach ($this->_relations as $class => $config) {
            $this->{'import' . ucfirst($class)}($config);
        }
        return true;
    }

    public function __call($name, $arguments)
    {
        $config = $arguments[0];
        $key = (strpos('import', $name) === 0) ? strtolower(str_replace('import', '', $name)) : '';
        if ($key !== '') {
            $connectorName = $config['connector'];

            $connector = new $connectorName($this->_application, $config['config']);
            $importer = new Importer($this->_application, $connector, $this->_config);
            $flushEvery = array_key_exists('flush_every', $config) ? (int)$config['flush_every'] : 1000;
            $checkForExisting = array_key_exists('check_exists', $config) ? (boolean)$config['check_exists'] : true;
            $importer->run($key, $config, $flushEvery, $checkForExisting);
        }
    }
}