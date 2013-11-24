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

namespace BackBuilder\Util\Transport;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @subpackage  Transport
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
abstract class ATransport
{

    protected $_protocol;
    protected $_host;
    protected $_port;
    protected $_username;
    protected $_password;
    protected $_remotepath = "/";

    public function __construct(array $config = null)
    {
        if (null !== $config) {
            if (array_key_exists('protocol', $config))
                $this->_protocol = $config['protocol'];
            if (array_key_exists('host', $config))
                $this->_host = $config['host'];
            if (array_key_exists('port', $config))
                $this->_port = $config['port'];
            if (array_key_exists('username', $config))
                $this->_username = $config['username'];
            if (array_key_exists('password', $config))
                $this->_password = $config['password'];
            if (array_key_exists('remotepath', $config))
                $this->_remotepath = $config['remotepath'];
        }
    }

    public abstract function connect($host = null, $port = null);

    public abstract function login($username = null, $password = null);

    public abstract function cd($dir = null);

    public abstract function ls($dir = null);

    public abstract function pwd();

    public abstract function send($local_file, $remote_file, $overwrite = false);

    public abstract function sendRecursive($local_path, $remote_path, $overwrite = false);

    public abstract function get($local_file, $remote_file, $overwrite = false);

    public abstract function getRecursive($local_path, $remote_path, $overwrite = false);

    public abstract function mkdir($dir, $recursive = false);

    public abstract function delete($remote_path, $recursive = false);

    public abstract function disconnect();
}