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

namespace BackBuilder\Importer;

use BackBuilder\BBApplication,
    BackBuilder\Config\Config,
    BackBuilder\Importer\Exception\SkippedImportException;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @copyright   Lp digital system
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

        if (0 == count($this->_relations)) {
            return false;
        }

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
        $config = $arguments[0];
        $key = (strpos($name, 'import') === 0) ? strtolower(str_replace('import', '', $name)) : '';
        if ($key !== '') {
            $connectorName = '\BackBuilder\Importer\Connector\\' . $config['connector'];

            $connector = new $connectorName($this->_application, $this->_config->getSection($config['config']));
            $importer = new Importer($this->_application, $connector, $this->_config);
            $flushEvery = array_key_exists('flush_every', $config) ? (int) $config['flush_every'] : 1000;
            $checkForExisting = array_key_exists('check_exists', $config) ? (boolean) $config['check_exists'] : true;
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