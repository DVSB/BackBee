<?php
namespace BackBuilder\Util\Transport;

abstract class ATransport {
    protected $_protocol;
    protected $_host;
    protected $_port;
    protected $_username;
    protected $_password;
    protected $_remotepath="/";
    
    public function __construct(array $config = null) {
        if (null !== $config) {
            if (array_key_exists('protocol', $config)) $this->_protocol = $config['protocol'];
            if (array_key_exists('host', $config)) $this->_host = $config['host'];
            if (array_key_exists('port', $config)) $this->_port = $config['port'];
            if (array_key_exists('username', $config)) $this->_username = $config['username'];
            if (array_key_exists('password', $config)) $this->_password = $config['password'];
            if (array_key_exists('remotepath', $config)) $this->_remotepath = $config['remotepath'];
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