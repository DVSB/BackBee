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
    BackBuilder\Importer\IImporterConnector,
    BackBuilder\Util\File,
    BackBuilder\Util\Dir;

/**
 * 
 * Zip Archive connector. 
 * 
 * Unzips archive into a tmp dir for faster processing
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @subpackage  Connector
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class ZipArchive implements IImporterConnector
{

    /**
     * @var BackBuilder\BBApplication
     */
    private $_application;

    /**
     * @var array
     */
    private $_config = array();
    
    /**
     * 
     * @var FileSystem
     */
    private $fileSystemConnector;

    /**
     * 
     * @var bool
     */
    private $isInitialised = false;
    
    /**
     * Class constructor
     * @param \BackBuilder\BBApplication $application
     * @param array $config
     */
    public function __construct(BBApplication $application, array $config)
    {
        $this->_application = $application;
        
        if(!isset($config['archive'])) {
            throw new \Exception("Configuration param 'archive' must be provided");
        }
        
        $this->_config = array_merge_recursive(array(
            'extractedDir' => $application->getTemporaryDir() . '/ZipArchiveConnector/extracted/' . basename($config['archive']) . '/' . date('Y-m-d_His') . '/'
        ), $config);
        
        $this->_init();
    }

    /**
     * Get config param
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig($key, $default = null) 
    {
        if(array_key_exists($key, $this->_config)) {
            return $this->_config[$key];
        }
        
        return $default;
    }

    /**
     * Get the root dir of the extracted archive
     * 
     * @return string
     */
    public function getBaseDir()
    {
        return $this->getConfig('extractedDir');
    }
    
    /**
     * 
     */
    private function _init()
    {
        if($this->isInitialised) {
            return;
        }

        if(false === file_exists($this->getConfig('extractedDir'))) {
            $res = mkdir($this->getConfig('extractedDir'), 0777, true);

            if(false === $res) {
                throw new \Exception('Could not create tmp dir: ' . $this->getConfig('extractedDir'));
            }
        }

        $archiveFile = $this->getConfig('archive');
                
        // if remote file, copy to tmp dir
        if(0 === strpos($archiveFile, 'https://') || 0 === strpos($archiveFile, 'http://') || 0 === strpos($archiveFile, 'ftp://')) {
            $tempFile = tempnam(sys_get_temp_dir(), basename($archiveFile));
            copy($archiveFile, $tempFile);
            
            $archiveFile = $tempFile;
        }
        
        File::extractZipArchive($archiveFile, $this->getConfig('extractedDir'), true);
        
        // delegate to FileSystem connector
        $this->fileSystemConnector = new FileSystem($this->_application, array(
            'baseDir' => $this->getConfig('extractedDir')
        ));
        
        $this->isInitialised = true;
    }
    
    /**
     * Return the path files according to the provided pattern
     *
     * @param string $pattern file pattern
     * @return array
     */
    public function find($pattern)
    {
        return $this->fileSystemConnector->find($pattern);
    }
    
    /**
     * 
     */
    public function cleanUp()
    {
        $this->isInitialised = false;
        Dir::delete($this->getConfig('extractedDir'));
    }

}
