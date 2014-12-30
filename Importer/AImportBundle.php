<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\Importer;

use BackBee\BBApplication;
use BackBee\Config\Config;
use BackBee\Importer\Exception\SkippedImportException;

/**
 * @category    BackBee
 * @package     BackBee\Import
 * @copyright   Lp system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
abstract class AImportBundle
{
    /**
     * @var string
     */
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

    /**
     * AImportBundle's constructor
     *
     * @param BBApplication $application
     */
    public function __construct(BBApplication $application)
    {
        $this->_application = $application;
        $this->_config = new Config($this->_dir);
        foreach ($this->_application->getConfig()->getSection('doctrine') as $key => $db_config) {
            $this->_config->setSection($key, $db_config, true);
        }

        $this->_relations = $this->_config->getSection('relations');

        if (0 == count($this->_relations)) {
            return false;
        }

        $log_filepath = $application->getConfig()->getLoggingConfig();
        $log_filepath = $log_filepath['logfile'];

        if ('/' !== $log_filepath[0] || false === is_dir(dirname($log_filepath))) {
            $log_filepath = $application->getBaseDir().'/log/import.log';
        } else {
            $log_filepath = dirname($log_filepath).'/import.log';
        }

        $logger = new \BackBee\Logging\Appender\File(array(
            'logfile' => $log_filepath,
        ));

        $this->setPhpConf($this->_config->getSection('php_ini'));
        foreach ($this->_relations as $class => $config) {
            $type = true === isset($config['type']) ? $config['type'] : 'import';
            try {
                $this->{$type.ucfirst($class)}($config);
            } catch (SkippedImportException $exc) {
                echo $exc->getMessage()."\n";
            } catch (\Exception $e) {
                $logger->write(array(
                    'd' => date('Y/m/d H:i:s'),
                    'p' => '',
                    'm' => $e->getMessage(),
                    'u' => '', )
                );
            }
        }

        return true;
    }

    public function __call($name, $arguments)
    {
        $config = reset($arguments);

        if (true === isset($config['do_import']) && false === $config['do_import']) {
            $this->markAsSkipped('`'.str_replace('import', '', $name).'`');
        }

        $key = (0 === strpos($name, 'import')) ? strtolower(str_replace('import', '', $name)) : '';
        if ($key !== '') {
            $connectorName = '\BackBee\Importer\Connector\\'.$config['connector'];

            $connector = new $connectorName($this->_application, $this->_config->getSection($config['config']));
            $importer = new Importer($this->_application, $connector, $this->_config);
            $flushEvery = array_key_exists('flush_every', $config) ? (int) $config['flush_every'] : 1000;
            $checkForExisting = array_key_exists('check_exists', $config) ? (boolean) $config['check_exists'] : true;
            $importer->run($key, $config, $flushEvery, $checkForExisting);
            unset($connector);
            unset($importer);
        }
    }

    protected function markAsSkipped($name)
    {
        throw new SkippedImportException(ucfirst($name).' importation has been skipped.');
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
