<?php
namespace BackBuilder\Importer;

use BackBuilder\BBApplication,
    BackBuilder\Config\Config,
    BackBuilder\Importer\Exception\SkippedImportException;

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
     * @var Config
     */
    protected $_config;
    /**
     * @var array
     */
    protected $_relations;
    /**
     * @var BBApplication
     */
    protected $_application;

    public function __construct(BBApplication $application)
    {
        $this->_application = $application;
        $this->_config = new Config($this->_dir);
        $this->_relations = $this->_config->getSection('relations');

        if (0 == count($this->_relations)) {return false;}

        $this->setPhpConf($this->_config->getSection('php_ini'));
        foreach ($this->_relations as $class => $config) {
            try {
                $this->{'import' . ucfirst($class)}($config);
            } catch (SkippedImportException $exc) {
                echo $exc->getMessage() . "\n";
            }
        }
        return true;
    }

    public function __call($name, $arguments)
    {
        $config = reset($arguments);
        $key = (0 === strpos($name, 'import')) ? strtolower(str_replace('import', '', $name)) : '';
        if ($key !== '') {
            $connectorName = '\BackBuilder\Importer\Connector\\' . $config['connector'];

            $connector = new $connectorName($this->_application, $this->_config->getSection($config['config']));
            $importer = new Importer($this->_application, $connector, $this->_config);
            $flushEvery = array_key_exists('flush_every', $config) ? (int)$config['flush_every'] : 1000;
            $checkForExisting = array_key_exists('check_exists', $config) ? (boolean)$config['check_exists'] : true;
            $importer->run($key, $config, $flushEvery, $checkForExisting);
        }
    }

    protected function markAsSkipped($name)
    {
        throw new SkippedImportException(ucfirst($name) . ' importation has been skipped.');
    }

    protected function setPhpConf($config)
    {
        if (is_array($config)) {
            $maxExecTime = (array_key_exists('max_execution_time', $config)) ? $config['max_execution_time'] : 0;
            $memLimit = (array_key_exists('memory_limit', $config)) ? $config['memory_limit'] : '2048M';
        } else {
            $memLimit = '2048M';
            $maxExecTime = 0;
        }

        ini_set('max_execution_time', $maxExecTime);
        ini_set('memory_limit', $memLimit);
    }
}