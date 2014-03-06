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

namespace BackBuilder\Stream\ClassWrapper\Adapter;

use BackBuilder\Exception\BBException,
    BackBuilder\Util\File,
    BackBuilder\Stream\ClassWrapper\AClassWrapper,
    BackBuilder\Stream\ClassWrapper\Exception\ClassWrapperException;
use Symfony\Component\Yaml\Exception\ParseException,
    Symfony\Component\Yaml\Yaml as parserYaml;

/**
 * Stream wrapper to interprate yaml file as class content description
 * Extends AClassWrapper
 *
 * @category    BackBuilder
 * @package     BackBuilder\Stream\ClassWrapper
 * @subpackage  Adapter
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Yaml extends AClassWrapper
{

    /**
     * Current BackBuilder application
     * @var BackBuilder\BBApplication
     */
    private $_application;

    /**
     * Extensions to include searching file
     * @var array
     */
    private $_includeExtensions = array('.yml', '.yaml');

    /**
     * Path to the yaml file
     * @var string
     */
    private $_path;

    /**
     * Ordered directories file path to look for yaml file
     * @var array
     */
    private $_classcontentdir;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (NULL === $this->_autoloader) {
            throw new ClassWrapperException('The BackBuilder autoloader can not be retreived.');
        }

        $this->_application = $this->_autoloader->getApplication();
        if (NULL !== $this->_application) {
            $this->_classcontentdir = $this->_application->getClassContentDir();
        }

        if (NULL === $this->_classcontentdir || 0 == count($this->_classcontentdir)) {
            throw new ClassWrapperException('None ClassContent repository defined.');
        }
    }

    /**
     * Extract and format datas from parser
     * @param array $datas
     * @return array The extracted datas
     */
    protected function _extractDatas($datas)
    {
        $extractedDatas = array();

        foreach ($datas as $key => $value) {
            $type = 'scalar';
            $options = array();

            if (is_array($value)) {
                if (isset($value['type'])) {
                    $type = $value['type'];
                    if (isset($value['default']))
                        $options['default'] = $value['default'];
                    if (isset($value['label']))
                        $options['label'] = $value['label'];
                    if (isset($value['maxentry']))
                        $options['maxentry'] = $value['maxentry'];
                    if (isset($value['parameters']))
                        $options['parameters'] = $this->_extractDatas($value['parameters']);
                } else {
                    $type = 'array';
                    $options['default'] = $value;
                }
            } else {
                $value = trim($value);

                if (strpos($value, '!!') === 0) {
                    $typedValue = explode(' ', $value, 2);
                    $type = str_replace('!!', '', $typedValue[0]);
                    if (isset($typedValue[1]))
                        $options['default'] = $typedValue[1];
                }
            }

            $extractedDatas[$key] = array('type' => $type, 'options' => $options);
        }

        return $extractedDatas;
    }

    /**
     * Checks the validity of the extracted data from yaml file
     * @param array $yamlDatas The yaml datas
     * @return Boolean Returns TRUE if datas are valid, FALSE if not
     * @throws ClassWrapperException Occurs when datas are not valid
     */
    private function _checkDatas($yamlDatas)
    {
        try {
            if ($yamlDatas === false || !is_array($yamlDatas) || count($yamlDatas) > 1) {
                throw new ClassWrapperException('Malformed class content description');
            }

            foreach ($yamlDatas as $classname => $contentDesc) {
                if ($this->classname != $this->_normalizeVar($this->classname)) {
                    throw new ClassWrapperException("Class Name don't match with the filename");
                }

                if (!is_array($contentDesc)) {
                    throw new ClassWrapperException('None class content description found');
                }

                foreach ($contentDesc as $key => $datas) {
                    switch ($key) {
                        case 'extends':
                            $this->extends = $this->_normalizeVar($datas, true);
                            if (substr($this->extends, 0, 1) != NAMESPACE_SEPARATOR) {
                                $this->extends = NAMESPACE_SEPARATOR . $this->namespace .
                                        NAMESPACE_SEPARATOR . $this->extends;
                            }
                            break;
                        case 'repository':
                            $this->repository = $this->_normalizeVar($datas, true);
                            if (substr($this->repository, 0, 1) != NAMESPACE_SEPARATOR) {
                                $this->repository = NAMESPACE_SEPARATOR . $this->namespace .
                                        NAMESPACE_SEPARATOR . $this->repository;
                            }
                            break;
                        case 'traits':
                            $datas = false === is_array($datas) ? array($datas) : $datas;
                            $this->traits = array();

                            foreach ($datas as $t) {
                                $trait = $t;
                                if (NAMESPACE_SEPARATOR !== substr($t, 0, 1)) {
                                    $trait = NAMESPACE_SEPARATOR . $t;
                                }

                                $this->traits[] = $trait;
                            }

                            $str = implode(', ', $this->traits);
                            if (0 < count($this->traits)) {
                                $this->traits = 'use ' . $str . ';';
                            } else {
                                $this->traits = '';
                            }

                            break;
                        case 'elements':
                        case 'parameters':
                        case 'properties':
                            $values = array();
                            $datas = (array) $datas;
                            foreach ($datas as $var => $value) {
                                $values[strtolower($this->_normalizeVar($var))] = $value;
                            }
                            $this->$key = $values;
                            break;
                    }
                }
            }
        } catch (ClassWrapperException $e) {
            throw new ClassWrapperException($e->getMessage(), 0, NULL, $this->_path);
        }

        return true;
    }

    /**
     * Return the real yaml file path of the loading class
     * @param string $path
     * @return string The real path if found
     */
    private function _resolveFilePath($path)
    {
        $path = str_replace(array($this->_protocol . '://', '/'), array('', DIRECTORY_SEPARATOR), $path);

        foreach ($this->_includeExtensions as $ext) {
            $filename = $path . $ext;
            File::resolveFilepath($filename, NULL, array('include_path' => $this->_classcontentdir));
            if (true === is_file($filename)) {
                return $filename;
            }
        }

        return $path;
    }

    /**
     * @see IClassWrapper::glob()
     */
    public function glob($pattern)
    {
        $classnames = array();
        foreach ($this->_classcontentdir as $repository) {
            foreach ($this->_includeExtensions as $ext) {
                if (FALSE !== $files = glob($repository . DIRECTORY_SEPARATOR . $pattern . $ext)) {
                    foreach ($files as $file) {
                        $classnames[] = $this->namespace . NAMESPACE_SEPARATOR . str_replace(array($repository . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), array('', NAMESPACE_SEPARATOR), $file);
                    }
                }
            }
        }

        if (0 == count($classnames)) {
            return FALSE;
        }

        foreach ($classnames as &$classname) {
            $classname = str_replace($this->_includeExtensions, '', $classname);
        }
        unset($classname);

        return array_unique($classnames);
    }

    /**
     * Opens a stream content for a yaml file
     * @see BackBuilder\Stream\ClassWrapper.IClassWrapper::stream_open()
     * @throws BBException Occurs when none yamel files were found
     * @throws ClassWrapperException Occurs when yaml file is not a valid class content description
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $path = str_replace(array($this->_protocol . '://', '/'), array('', DIRECTORY_SEPARATOR), $path);

        $this->classname = basename($path);
        if (dirname($path) && dirname($path) != DIRECTORY_SEPARATOR) {
            $this->namespace .= NAMESPACE_SEPARATOR .
                    str_replace(DIRECTORY_SEPARATOR, NAMESPACE_SEPARATOR, dirname($path));
        }

        $this->_path = $this->_resolveFilePath($path);
        if (is_file($this->_path) && is_readable($this->_path)) {
            $this->_stat = @stat($this->_path);

            if (NULL !== $this->_cache) {
                $expire = new \DateTime();
                $expire->setTimestamp($this->_stat['mtime']);
                $this->_data = $this->_cache->load(md5($this->_path), false, $expire);

                if (false !== $this->_data) {
                    return true;
                }
            }

            try {
                $yamlDatas = parserYaml::parse($this->_path);
            } catch (ParseException $e) {
                throw new ClassWrapperException($e->getMessage());
            }

            if ($this->_checkDatas($yamlDatas)) {
                $this->_data = $this->_buildClass();
                $opened_path = $this->_path;

                if (NULL !== $this->_cache) {
                    $this->_cache->save(md5($this->_path), $this->_data);
                }

                return true;
            }
        }

        throw new BBException(sprintf('Class \'%s\' not found', $this->namespace . NAMESPACE_SEPARATOR . $this->classname));
    }

    /**
     * Retrieve information about a yaml file
     * @see BackBuilder\Stream\ClassWrapper.AClassWrapper::url_stat()
     */
    public function url_stat($path, $flag)
    {
        $path = str_replace(array($this->_protocol . '://', '/'), array('', DIRECTORY_SEPARATOR), $path);

        $this->_path = $this->_resolveFilePath($path);
        if (is_file($this->_path) && is_readable($this->_path)) {
            $fd = fopen($this->_path, 'rb');
            $this->_stat = fstat($fd);
            fclose($fd);

            return $this->_stat;
        }

        return NULL;
    }

}