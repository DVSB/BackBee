<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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

namespace BackBee\Installer;

use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\ORM\Mapping\Entity;

class EntityFinder
{
    /**
     * @var array
     */
    private $ignoredFolder = [
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
    ];

    /**
     * @var string
     */
    private $baseDir;

    /**
     * @var SimpleAnnotationReader
     */
    private $annotationReader;

    /**
     * @param string $baseDir
     */
    public function __construct($baseDir)
    {
        $this->baseDir = $baseDir;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public function getEntities($path)
    {
        $entities = [];

        $Directory = new \RecursiveDirectoryIterator($path);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $objects = new \RegexIterator($Iterator, '/.*(.php)$/', \RecursiveRegexIterator::GET_MATCH);

        foreach ($objects as $filename => $object) {
            $file = explode('/', $filename);
            if (!preg_filter($this->ignoredFolder, [], $file)) {
                $namespace = $this->getNamespace($filename);
                if ($this->isValidNamespace($namespace)) {
                    $entities[] = $namespace;
                }
            }
        }

        return $entities;
    }

    /**
     * Get the paths that should be excluded.
     *
     * @param string $path
     *
     * @return array
     */
    public function getExcludePaths($path)
    {
        $excludePaths = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $objects = new \RegexIterator($iterator, '/.*(.php)$/', \RecursiveRegexIterator::GET_MATCH);

        foreach ($objects as $filename => $object) {
            if (preg_filter($this->ignoredFolder, [], $filename)) {
                $excludePaths[] = dirname($filename);
            }
        }

        $excludePaths = array_unique($excludePaths);

        return $excludePaths;
    }

    /**
     * @param string $folder
     *
     * @return self
     */
    public function addIgnoredFolder($folder)
    {
        $this->ignoredFolder[] = $folder;

        return $this;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    public function getNamespace($file)
    {
        $classname = str_replace([
                $this->baseDir,
                'bundle',
                '.php',
                '/',
                'backbee',
            ],
            [
                '',
                'Bundle',
                '',
                '\\',
                '',
            ],
            $file
        );
        $classname = preg_replace('/\\\\+/', '\\', $classname);

        return (strpos($classname, 'BackBee') === false) ? 'BackBee'.$classname : $classname;
    }

    /**
     * @param string $namespace
     *
     * @return bool
     */
    private function isValidNamespace($namespace)
    {
        return (
            true === class_exists($namespace)
            && $this->isEntity(new \ReflectionClass($namespace))
        );
    }

    /**
     * @param \ReflectionClass $reflection
     *
     * @return boolean
     */
    private function isEntity(\ReflectionClass $reflection)
    {
        return (!is_null($this->getEntityAnnotation($reflection)));
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return Entity
     */
    private function getEntityAnnotation(\ReflectionClass $class)
    {
        die('coucou');
        if (!$this->annotationReader) {
            $this->annotationReader = new SimpleAnnotationReader();
            $this->annotationReader->addNamespace('Doctrine\ORM\Mapping');
        }

        return $this->annotationReader->getClassAnnotation($class, new Entity());
    }
}
