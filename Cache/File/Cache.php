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

namespace BackBuilder\Cache\File;

use BackBuilder\Cache\ACache,
    BackBuilder\Cache\Exception\CacheException,
    BackBuilder\Util\String;
use Psr\Log\LoggerInterface;

/**
 * Filesystem cache adapter
 * 
 * A simple cache system storing data in files, it does not provide tag or expire features
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache
 * @subpackage  File
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Cache extends ACache
{

    /**
     * The cache directory
     * @var string
     */
    private $_cachedir;

    /**
     * Memcached adapter options
     * @var array
     */
    protected $_instance_options = array(
        'cachedir' => null
    );

    /**
     * Class constructor
     * @param array $options Initial options for the cache adapter:
     *                         - cachedir string The cache directory
     * @param string $context An optional cache context
     * @param \Psr\Log\LoggerInterface $logger An optional logger
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if the cache directory doesn't exist, can not
     *                                                     be created or is not writable.
     */
    public function __construct(array $options = array(), $context = null, LoggerInterface $logger = null)
    {
        parent::__construct($options, $context, $logger);
        $this->log('info', sprintf('File cache system initialized with directory set to `%s`.', $this->_cachedir));
    }

    /**
     * Sets the memcache adapter instance options
     * @param array $options
     * @return \BackBuilder\Cache\File\Cache
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if the cache directory doesn't exist and it cannot
     *                                                     be created or if it is not writable.
     */
    protected function setInstanceOptions(array $options = array())
    {
        parent::setInstanceOptions($options);

        $this->_cachedir = $this->_instance_options['cachedir'];

        if (null !== $this->getContext()) {
            $this->_cachedir .= DIRECTORY_SEPARATOR . String::toPath($this->getContext());
        }

        if (false === is_dir($this->_cachedir)
                && false === @mkdir($this->_cachedir, 0755, true)) {
            throw new CacheException(sprintf('Unable to create the cache directory `%s`.', $this->_cachedir));
        }

        if (false === is_writable($this->_cachedir)) {
            throw new CacheException(sprintf('Unable to write in the cache directory `%s`.', $this->_cachedir));
        }

        return $this;
    }

    /**
     * Returns the available cache for the given id if found returns false else
     * @param string $id Cache id
     * @param boolean $bypassCheck Allow to find cache without test it before
     * @param \DateTime $expire Optionnal, the expiration time (now by default)
     * @return string|FALSE
     */
    public function load($id, $bypassCheck = false, \DateTime $expire = null)
    {
        if (null === $expire) {
            $expire = new \DateTime();
        }

        $last_timestamp = $this->test($id);
        if (true === $bypassCheck
                || 0 === $last_timestamp
                || $expire->getTimestamp() <= $last_timestamp) {

            if (false !== $data = @file_get_contents($this->_getCacheFile($id))) {
                $this->log('debug', sprintf('Reading file cache data for id `%s`.', $id));
                return $data;
            }

            $this->log('warning', sprintf('Enable to read data in file cache for id `%s`.', $id));
        } else {
            $this->log('debug', sprintf('None available file cache found for id `%s` newer than %s.', $id, $expire->format('d/m/Y H:i')));
        }

        return false;
    }

    /**
     * Tests if a cache is available or not (for the given id)
     * @param string $id Cache id
     * @return int|FALSE the last modified timestamp of the available cache record
     */
    public function test($id)
    {
        if (false === $stat = @stat($this->_getCacheFile($id))) {
            return false;
        }

        return $stat['mtime'];
    }

    /**
     * Save some string datas into a cache record
     * @param string $id Cache id
     * @param string $data Datas to cache
     * @param int $lifetime Optional, the specific lifetime for this record 
     *                      (by default null, infinite lifetime)
     * @param string $tag Optional, an associated tag to the data stored
     * @return boolean TRUE if cache is stored FALSE otherwise
     */
    public function save($id, $data, $lifetime = null, $tag = null)
    {
        if (null !== $lifetime || null !== $tag) {
            $this->log('warning', sprintf('Lifetime and tag features are not available for cache adapter `%s`.', get_class($this)));
        }

        if (true === $result = @file_put_contents($this->_getCacheFile($id), $data)) {
            $this->log('debug', sprintf('Storing data in cache for id `%s`.', $id));
        } else {
            $this->log('warning', sprintf('Unable to save data to file cache for id `%s` in directory `%s`.', $id, $this->_cachedir));
        }

        return (false !== $result);
    }

    /**
     * Removes a cache record
     * @param  string $id Cache id
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    public function remove($id)
    {
        if (true === $result = @unlink($this->_getCacheFile($id))) {
            $this->log('debug', sprintf('Cache data removed for id `%s`.', $id));
        }

        return $result;
    }

    /**
     * Clears all cache records
     * @return boolean TRUE if cache is cleared FALSE otherwise
     */
    public function clear()
    {
        $result = false;

        if (false !== $files = @scandir($this->_cachedir)) {
            foreach ($files as $file) {
                if (false === is_dir($file)) {
                    if (false === $remove = @unlink($file)) {
                        $this->log('warning', sprintf('Enable to remove cache file `%s`.', $file));
                        $result = false;
                    }
                }
            }
        }

        if (true === $result) {
            $this->log('debug', sprintf('File system cache cleared in `%s`.', $this->_cachedir));
        }

        return $result;
    }

    /**
     * Returns the cache file if a cache is available for the given id
     * @param string $id Cache id
     * @return string The file path of the available cache record
     * @codeCoverageIgnore
     */
    private function _getCacheFile($id)
    {
        $cachefile = $this->_cachedir . DIRECTORY_SEPARATOR . $id;
        return $cachefile;
    }

}