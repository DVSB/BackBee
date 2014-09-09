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

namespace BackBuilder\Rest\Metadata\Cache;

use Metadata\ClassMetadata,
    Metadata\Cache\CacheInterface;

/**
 * Metadata file cache
 *
 * @Annotation
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class FileCache implements CacheInterface
{
    /**
     * the absolute path to directory where to put file cache
     *
     * @var string
     */
    private $dir;

    /**
     * the application debug value
     *
     * @var boolean
     */
    private $debug;

    /**
     * FileCache constructor
     *
     * @param string  $dir
     * @param boolean $debug
     */
    public function __construct($dir, $debug = false)
    {
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException(sprintf('The directory "%s" does not exist.', $dir));
        }
        if (!is_writable($dir)) {
            throw new \InvalidArgumentException(sprintf('The directory "%s" is not writable.', $dir));
        }

        $this->dir = rtrim($dir, '\\/');
        $this->debug = (bool) $debug;
    }

    /**
     * {@inheritDoc}
     */
    public function loadClassMetadataFromCache(\ReflectionClass $class)
    {
        $path = $this->dir.'/'.strtr($class->name, '\\', '-').'.cache.php';
        if (!file_exists($path)) {
            return null;
        }

        return include $path;
    }

    /**
     * {@inheritDoc}
     */
    public function putClassMetadataInCache(ClassMetadata $metadata)
    {
        if (true === $this->debug) {
            return;
        }

        $path = $this->dir.'/'.strtr($metadata->name, '\\', '-').'.cache.php';

        $tmpFile = $this->dir . '/' . uniqid('metadata_cache_', true);

        @file_put_contents($tmpFile, '<?php return unserialize('.var_export(serialize($metadata), true).');');
        chmod($tmpFile, 0666 & ~umask());


        if (false === @rename($tmpFile, $path)) {
            throw new \RuntimeException(sprintf('Could not write new cache file to %s.', $path));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function evictClassMetadataFromCache(\ReflectionClass $class)
    {
        $path = $this->dir.'/'.strtr($class->name, '\\', '-').'.cache.php';
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
