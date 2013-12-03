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

namespace BackBuilder\Importer\Connector;

use BackBuilder\BBApplication,
    BackBuilder\Importer\IImporterConnector;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @subpackage  Connector
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Soap implements IImporterConnector
{
    private $_config;
    /**
     * @var BackBuilder\BBApplication
     */
    private $_application;

    private $_content;

    public function __construct(BBApplication $application, array $config)
    {
        $this->_application = $application;
        $this->_config = $config;
        $soap_client = new \SoapClient($config['host'], $config['soap_options']);
        $params = array_key_exists('params', $config['soap_call']) ? $config['soap_call']['params'] : array();
        $xml = $soap_client->__soapCall($config['soap_call']['function'], $params);
        $this->_content = simplexml_load_string($xml);
    }

    /**
     * Single interface to find
     *
     * @param string $string
     * @return array
     */
    public function find($string)
    {
        if ($this->_content->getName() == $string) {
            $values = $this->_content;
        } else {
            $values = $this->_recursiveSearch($this->_content, $string);
        }
        $result = (array)$values;
        return reset($result);
    }

    private function _recursiveSearch($nodes, $key)
    {
        foreach ($nodes as $node) {
            if ($node->getName() == $key) {
                $result =  $node;
                break;
            } elseif (count($node) > 0) {
                $this->_recursiveSearch($node->children, $key);
            }
        }
        return $result;
    }
}