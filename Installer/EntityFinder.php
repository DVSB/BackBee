<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Installer;

use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\ORM\Mapping\Entity;

class EntityFinder
{
    /**
     * @var array
     */
    private $_ignoredFolder = array(
        '/Resources/',
        '/Ressources/',
        '/Test/',
        '/Tests/',
        '/Mock/',
        '/TestUnit/',
        '/Exception/',
        '/Commands/',
        '/Installer/',
        '/Assets/',
        '/Renderer/',
        '/Templates/',
        '/helpers/',
    );

    /**
     * @var string
     */
    private $_baseDir;

    /**
     * @var SimpleAnnotationReader
     */
    private $_annotationReader;

    /**
     *
     * @param string $baseDir
     */
    public function __construct($baseDir)
    {
        $this->_baseDir = $baseDir;
    }

    /**
     * @param  string $path
     * @return array
     */
    public function getEntities($path)
    {
        $entities = array();

        $Directory = new \RecursiveDirectoryIterator($path);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $objects = new \RegexIterator($Iterator, '/.*(.php)$/', \RecursiveRegexIterator::GET_MATCH);

        foreach ($objects as $filename => $object) {
            $file = explode('/', $filename);
            if (!preg_filter($this->_ignoredFolder, array(), $file)) {
                $namespace = $this->getNamespace($filename);
                if ($this->_isValidNamespace($namespace)) {
                    $entities[] = $namespace;
                }
            }
        }

        return $entities;
    }

    /**
     * Get the paths that should be excluded
     *
     * @param  string $path
     * @return array
     */
    public function getExcludePaths($path)
    {
        $excludePaths = array();

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $objects = new \RegexIterator($iterator, '/.*(.php)$/', \RecursiveRegexIterator::GET_MATCH);

        foreach ($objects as $filename => $object) {
            if (preg_filter($this->_ignoredFolder, array(), $filename)) {
                $excludePaths[] = dirname($filename);
            }
        }

        $excludePaths = array_unique($excludePaths);

        return $excludePaths;
    }

    public function parseFiles()
    {
    }

    public function addIgnoredFolder($folder)
    {
        $this->_ignoredFolder[] = $folder;
    }

    /**
     * @param  string $file
     * @return string
     */
    public function getNamespace($file)
    {
        $classname = str_replace(array($this->_baseDir, 'bundle', '.php', '/'), array('', 'Bundle', '', '\\'), $file);

        return (strpos($classname, 'BackBee') === false) ? 'BackBee'.$classname : $classname;
    }

    /**
     *
     * @param  string $namespace
     * @return bool
     */
    private function _isValidNamespace($namespace)
    {
        return (
            true === class_exists($namespace) &&
            $this->_isEntity(new \ReflectionClass($namespace))
        );
    }

    /**
     * @param  \ReflectionClass $reflection
     * @return boolean
     */
    private function _isEntity(\ReflectionClass $reflection)
    {
        return (!is_null($this->_getEntityAnnotation($reflection)));
    }

    /**
     * @param  \ReflectionClass $class
     * @return Entity
     */
    private function _getEntityAnnotation(\ReflectionClass $class)
    {
        if (!$this->_annotationReader) {
            $this->_annotationReader = new SimpleAnnotationReader();
            $this->_annotationReader->addNamespace('Doctrine\ORM\Mapping');
        }

        return $this->_annotationReader->getClassAnnotation($class, new Entity());
    }
}
