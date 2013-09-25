<?php
namespace BackBuilder\Util\Transport;

use BackBuilder\Util\Transport\Exception\TransportException;

class FileSystem extends ATransport {
    public function __construct(array $config = null) {
        parent::__construct($config);
        
        if (null !== $this->_remotepath && false === file_exists($this->_remotepath)) {
            @mkdir($this->_remotepath, 0755, true);
        }
    }
    
    public function connect($host = null, $port = null) {
        return $this;
    }
    
    public function login($username = null, $password = null) {
        return $this;
    }
    
    public function cd($dir = null) {
        $dir = null !== $dir ? $dir : $this->_remotepath;
        
        if ( false === chdir($dir) )
            throw new TransportException(sprintf('Enable to change remote directory to %s.', $dir));

        return true;        
    }
    
    public function ls($dir = null) {
        $dir = null !== $dir ? $dir : $this->pwd();
        
        if (false === $ls = @scandir($dir) ) {
            throw new TransportException(sprintf('Enable to list files of remote directory %s.', $dir));
        }
        
        return $ls;
    }
    
    public function pwd() {
        if (false === $pwd = getcwd() ) {
            throw new TransportException('Enable to obtain current directory to %s.');
        }
        
        return getcwd();
    }
    
    public function send($local_file, $remote_file, $overwrite = false) {
        $remote_file = $this->pwd() . DIRECTORY_SEPARATOR . $remote_file;
        if (true === file_exists($remote_file) && false === $overwrite) {
            return false;
        }
        
        if (false === copy($local_file, $remote_file)) {
            throw new TransportException(sprintf('Enable to write file %s.', $remote_file));
        }
        
        return true;
    }
    
    public function sendRecursive($local_path, $remote_path, $overwrite = false) {
        if (false === is_dir($local_path)) {
            return $this->send($local_path, $remote_path, $overwrite);
        }
        
        if (false === file_exists($this->pwd() . DIRECTORY_SEPARATOR . $remote_path)) {
            $this->mkdir($remote_path, true);
        } elseif (false === is_dir($this->pwd() . DIRECTORY_SEPARATOR . $remote_path)) {
            throw new TransportException(sprintf('A file named %s already exist, can\'t create folder.', $remote_path));
        }
        
        if (false === $lls = @scandir($local_path)) {
             throw new TransportException(sprintf('Enable to list files of local directory %s.', $local_path));
        }
        
        $currentpwd = $this->pwd();
        $this->cd($remote_path);
        foreach($lls as $file) {
            if ($file != "." && $file != "..") {
                $this->sendRecursive($local_path . DIRECTORY_SEPARATOR . $file, $file, $overwrite);
            }
        }
        $this->cd($currentpwd);
        
        return true;
    }
    
    public function get($local_file, $remote_file, $overwrite = false) {
        $remote_file = $this->pwd() . DIRECTORY_SEPARATOR . $remote_file;
        if (true === file_exists($local_file) && false === $overwrite) {
            return false;
        }
        
        if (false === copy($remote_file, $local_file)) {
            throw new TransportException(sprintf('Enable to write local file %s.', $local_file));
        }
        
        return true;
    }
    
    public function getRecursive($local_path, $remote_path, $overwrite = false) {
        if (false === is_dir($this->pwd() . DIRECTORY_SEPARATOR . $remote_path)) {
            return $this->get($local_path, $remote_path, $overwrite);
        }
        
        if (false === file_exists($local_path)) {
            if (false === @mkdir($local_path, 0755, true)) {
                throw new TransportException(sprintf('Enable to create local folder %s.', $local_path));
            }
        } elseif (false === is_dir($local_path)) {
            throw new TransportException(sprintf('A file named %s already exist, can\'t create folder.', $local_path));
        }
        
        $currentpwd = $this->pwd();
        $this->cd($remote_path);
        foreach($this->ls() as $file) {
            if ($file != "." && $file != "..") {
                $this->getRecursive($local_path . DIRECTORY_SEPARATOR . $file, $file, $overwrite);
            }
        }
        $this->cd($currentpwd);
    }
    
    public function mkdir($dir, $recursive = false) {
        if (false === @mkdir($this->pwd() . DIRECTORY_SEPARATOR . $dir, 0755, $recursive)) {
            throw new TransportException(sprintf('Enable to create folder %s.', $dir));
        }
        
        return true;
    }
    
    public function delete($remote_path, $recursive = false) {
        if (false === file_exists($this->pwd() . DIRECTORY_SEPARATOR . $remote_path)) {
            throw new TransportException(sprintf('Enable to delete remote file %s.', $remote_path));
        }
        
        if (false === is_dir($this->pwd() . DIRECTORY_SEPARATOR . $remote_path)) {
            return @unlink($this->pwd() . DIRECTORY_SEPARATOR . $remote_path);
        } elseif (true === $recursive) {
            $currentpwd = $this->pwd();
            $this->cd($remote_path);
            foreach($this->ls() as $file) {
                if ($file != "." && $file != "..") {
                    $this->delete($file, $recursive);
                }
            }
            $this->cd($currentpwd);
            return @rmdir($this->pwd() . DIRECTORY_SEPARATOR . $remote_path);
        }
        
        return false;
    }
    
    public function disconnect() {
        return $this;
    }
}