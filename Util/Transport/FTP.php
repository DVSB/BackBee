<?php
namespace BackBuilder\Util\Transport;

use BackBuilder\Util\Transport\Exception\TransportException;

class FTP extends ATransport {
    protected $_port     = 21;
    private $_passive    = true;
    private $_mode       = FTP_ASCII;
    private $_ftp_stream = null;
    
    public function __construct(array $config = null) {
        parent::__construct($config);
        
        if (null !== $config) {
            if (array_key_exists('passive', $config)) $this->_passive = true === $config['passive'];
            if (array_key_exists('mode', $config)) $this->_mode = defined('FTP_'.strtoupper($config['mode'])) ? constant('FTP_'.strtoupper($config['mode'])) : $this->_mode;
        }
    }
    
    public function __destruct() {
       $this->disconnect();
    }
    
    public function connect($host = null, $port = null) {
        $this->_host = null !== $host ? $host : $this->_host;
        $this->_port = null !== $port ? $port : $this->_port;
        
        $this->_ftp_stream = ftp_connect($this->_host, $this->_port);
        if (false === $this->_ftp_stream)
            throw new TransportException(sprintf('Enable to connect to %s:%i.', $this->_host, $this->_port));
        
        return $this;
    }

    public function login($username = null, $password = null) {
        if (!$this->_ftp_stream)
            throw new TransportException(sprintf('None FTP connection available.'));
        
        $this->_username = null !== $username ? $username : $this->_username;
        $this->_password = null !== $password ? $password : $this->_password;
        
        if( false === ftp_login($this->_ftp_stream, $this->_username, $this->_password) )
            throw new TransportException(sprintf('Enable to log with username %s.', $this->_username));
        
        if( false === ftp_pasv($this->_ftp_stream, $this->_passive) )
            throw new TransportException(sprintf('Enable to change mode to passive=%b.', $this->_passive));
        
        return $this;
    }
    
    public function cd($dir = null) {
        if (!$this->_ftp_stream)
            throw new TransportException(sprintf('None FTP connection available.'));
        
        $dir = null !== $dir ? $dir : $this->_remotepath;
        if ( false === @ftp_chdir($this->_ftp_stream, $dir) )
            throw new TransportException(sprintf('Enable to change remote directory to %s.', $dir));

        return $this;
    }
    
    public function ls($dir = null) {
        if (!$this->_ftp_stream)
            throw new TransportException(sprintf('None FTP connection available.'));
        
        $dir = null !== $dir ? $dir : $this->pwd();
        if ( false === $ls = ftp_nlist($this->_ftp_stream, $dir) )
            $ls = array();
        
        return $ls;
    }

    public function pwd() {
        if (!$this->_ftp_stream)
            throw new TransportException(sprintf('None FTP connection available.'));
        
        if ( false === $pwd = ftp_pwd($this->_ftp_stream) )
            throw new TransportException(sprintf('Enable to get remote directory.'));
        
        return $pwd;
    }
    
    public function send($local_file, $remote_file, $overwrite = false) {
        if (!$this->_ftp_stream)
            throw new TransportException(sprintf('None FTP connection available.'));
        
        if (false === ftp_put($this->_ftp_stream, $remote_file, $local_file, $this->_mode))
            throw new TransportException(sprintf('Enable to put file.'));
        
        return false;
    }

    public function sendRecursive($local_path, $remote_path, $overwrite=false){
        return false;
    }

    public function get($local_file, $remote_file, $overwrite = false){
        if (!$this->_ftp_stream)
            throw new TransportException(sprintf('None FTP connection available.'));
        
        if ( false === ftp_get($this->_ftp_stream, $local_file, $remote_file, $this->_mode) )
            throw new TransportException(sprintf('Enable to get remote file %s to local file %s.', $remote_file, $local_file));
        
        return $this;
    }

    public function getRecursive($local_path, $remote_path, $overwrite = false){
        return false;
    }

    public function mkdir($dir, $recursive=false){
        return false;
    }

    public function delete($remote_path, $recursive=false){
        if (!$this->_ftp_stream)
            throw new TransportException(sprintf('None FTP connection available.'));
        
        if ( false === ftp_delete($this->_ftp_stream, $remote_path) )
            throw new TransportException(sprintf('Enable to remove file %s.', $remote_path));
        
        return $this;
    }
    
    public function disconnect(){
        if (!$this->_ftp_stream) return $this;

        @ftp_close($this->_ftp_stream);
          return $this;
    }
}