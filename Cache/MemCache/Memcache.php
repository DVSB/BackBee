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

namespace BackBuilder\Cache\MemCache;

use BackBuilder\Cache\AExtendedCache;
use BackBuilder\Cache\Exception\CacheException;
use Psr\Log\LoggerInterface;

/**
 * Memcache cache adapter
 *
 * It supports tag and expire features
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache
 * @subpackage  MemCache
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Memcache extends AExtendedCache
{

    /**
     * Default Memcache host
     * @var string
     */
    const DEFAULT_HOST = '127.0.0.1';

    /**
     * Default Memcache server port
     * @var int
     */
    const DEFAULT_PORT = 11211;

    /**
     * Default server weight
     * @var int
     */
    const DEFAULT_WEIGHT = 1;

    /**
     * Tags hash prefix
     * @var string
     */
    const TAGS_PREFIX = '__tag__';

    /**
     * Expire hash prefix
     * @var string
     */
    const EXPIRE_PREFIX = '__expire__';

    /**
     * Memcache adapter options
     * @var array
     */
    protected $_instance_options = array(
        'type' => 'memcache',
        'min_cache_lifetime' => null,
        'max_cache_lifetime' => null,
        'persistent_id' => null,
        'compression' => false,
        'servers' => array(),
        'options' => array()
    );

    /**
     * The Memcache object
     * @var \Memcache
     */
    private $_Memcache;

    private $compression = 0;

    private $serverList = array();
    /**
     * The default Memcache server connection information
     * @var array
     */
    private $_default_server = array(
        'host' => self::DEFAULT_HOST,
        'port' => self::DEFAULT_PORT,
        'weight' => self::DEFAULT_WEIGHT
    );

    /**
     * Override some default Memcache instance options
     * @var array
     */
    private $_Memcache_options = array(
        2 => 1, // OPT_HASH = HASH_MD5
        9 => 1, // OPT_DISTRIBUTION = DISTRIBUTION_CONSISTENT
        16 => true // OPT_LIBKETAMA_COMPATIBLE = true
    );

    /**
     * Class constructor
     * @param array $options Initial options for the cache adapter:
     *                         - persistent_id string Optional persistent key
     *                         - servers array The Memcache servers to add
     *                         - options array The Memcache options to set
     * @param string $context An optional cache context use as prefix key
     * @param \Psr\Log\LoggerInterface $logger An optional logger
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if Memcache extension is not available.
     */
    public function __construct(array $options = array(), $context = null, LoggerInterface $logger = null)
    {
        if (false === extension_loaded('Memcache')) {
            throw new CacheException('Memcache extension is not loaded');
        }

        $this->_Memcache = new \Memcache();

        parent::__construct($options, $context, $logger);
    }

    /**
     * Closes all the Memcache server connections if not persistent
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        $this->_Memcache->close();
    }

    /**
     * Sets the memcache adapter instance options
     * @param array $options
     * @return \BackBuilder\Cache\MemCache\Memcache
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if a provided option is unknown for this adapter.
     */
    protected function setInstanceOptions(array $options = array())
    {
        parent::setInstanceOptions($options);
        if (true === $this->_instance_options['compression']) {
            $this->compression = MEMCACHE_COMPRESSED;
        }

        if (false === is_array($this->_instance_options['servers'])) {
            throw new CacheException('Memcache adapter: Memcache servers is not an array.');
        }

        $this->addServers($this->_instance_options['servers']);

        return $this;
    }

    /**
     * Adds a server to the server pool
     * @param string $host The hostname of the memcache server
     * @param int $port The port on which memcache is running, 11211 by default
     * @param int $weight The weight of the server
     * @return boolean      TRUE on success or FALSE on failure.
     * @link http://php.net/manual/en/Memcache.addserver.php
     */
    public function addServer($host, $port, $weight = 0)
    {
        if (true === $this->_hasServer($host, $port)) {
            return true;
        }

        if (false === $this->_Memcache->addServer($host, $port, $weight)) {
            return $this->_onError('setOption');
        }

        return true;
    }

    /**
     * Adds a set of servers to the memcache instance pool
     * @param array $servers
     * @return boolean
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if one of the server configurations is not an array
     * @link http://php.net/manual/en/Memcache.addservers.php
     */
    public function addServers(array $servers = array())
    {
        $result = true;

        foreach ($servers as $server) {
            if (false === is_array($server)) {
                throw new CacheException('Memcache adapter: server configuration is not an array.');
            }

            $server = array_merge($this->_default_server, $server);
            $result = $result && $this->addServer($server['host'], $server['port'], true, $server['weight']);

            $this->setServerList($server);
        }

        // DISABLE AUTOMATIC COMPRESSION
        $this->_Memcache->setCompressThreshold(99999999, 0.2);

        // FIX Memcache redundancy
        ini_set("memcache.redundancy", count($this->getServerList()));

        return $result;
    }

    public function setServerList($server)
    {
        $this->serverList[] = $server;
    }

    /**
     * Returns the list of available memcached servers
     * @return array
     * @link http://php.net/manual/en/memcached.getserverlist.php
     * @codeCoverageIgnore
     */
    public function getServerList()
    {
        return $this->serverList;
    }

    /**
     * Returns the available cache for the given id if found returns FALSE else
     * @param string $id Cache id
     * @param boolean $bypassCheck Allow to find cache without test it before
     * @param \DateTime $expire Optionnal, the expiration time (now by default)
     * @return string|FALSE
     */
    public function load($id, $bypassCheck = true, \DateTime $expire = null)
    {
        if (null === $expire) {
            $expire = new \DateTime();
        }

        $last_timestamp = 0;
        if (false === $bypassCheck) {
            $last_timestamp = $this->test($id);
        }

        if (true === $bypassCheck || 0 === $last_timestamp || $expire->getTimestamp() <= $last_timestamp) {

            if (false === $tmp = $this->_Memcache->get($id)) {
                return $this->_onError('load');
            }

            return $tmp;
        }

        return false;
    }

    /**
     * Tests if a cache is available or not (for the given id)
     * @param string $id Cache id
     * @return int|FALSE the last modified timestamp of the available cache record (0 infinite expiration date)
     */
    public function test($id)
    {
        if (false === $tmp = $this->_Memcache->get(self::EXPIRE_PREFIX . $id)) {
            return false;
        }

        return (int)$tmp;
    }

    /**
     * Saves some string datas into a cache record
     * @param string $id Cache id
     * @param string $data Datas to cache
     * @param int $lifetime Optional, the specific lifetime for this record
     *                      (by default null, infinite lifetime)
     * @param string $tag Optional, an associated tag to the data stored
     * @return boolean TRUE if cache is stored FALSE otherwise
     */
    public function save($id, $data, $lifetime = null, $tag = null, $bypass_control = false)
    {
        if (null === $lifetime) {
            $lifetime = 0;
        }

        if (false === $bypass_control) {
            $lifetime = $this->getControledLifetime($lifetime);
        }

        if (false === $this->_Memcache->set($id, (is_array($data) ? $data : ''.$data), $this->compression, $lifetime) ||
            false === $this->_Memcache->set(self::EXPIRE_PREFIX . $id, ''.time() + $lifetime, $this->compression, $lifetime)
        ) {
            return $this->_onError('save');
        }

        if (null !== $tag) {
            if (false === $tagged = $this->load(self::TAGS_PREFIX . $tag)) {
                $tagged = array();
            }

            if (false === in_array($id, $tagged)) {
                $tagged[] = $id;
                $this->save(self::TAGS_PREFIX . $tag, $tagged);
            }
        }

        return true;
    }

    /**
     * Removes a cache record
     * @param  string $id Cache id
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    public function remove($id)
    {
        if (false === $this->_Memcache->delete($id) ||
            false === $this->_Memcache->delete(self::EXPIRE_PREFIX . $id)
        ) {
            return $this->_onError('remove');
        }

        return true;
    }

    /**
     * Clears all cache records
     * @return boolean TRUE if cache is cleared FALSE otherwise
     */
    public function clear()
    {
        if (false === $this->_Memcache->flush()) {
            return $this->_onError('clear');
        }

        return true;
    }

    /**
     * Removes all cache records associated to one of the tags
     * @param  string|array $tag
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    public function removeByTag($tag)
    {
        $tags = (array)$tag;
        if (0 === count($tags)) {
            return false;
        }

        foreach ($tags as $tag) {
            if (false !== $tagged = $this->load(self::TAGS_PREFIX . $tag)) {
                foreach ($tagged as $id) {
                    $this->remove($id);
                }
                $this->remove(self::TAGS_PREFIX . $tag);
            }
        }

        return true;
    }

    /**
     * Updates the expire date time for all cache records
     * associated to one of the provided tags
     * @param  string|array $tag
     * @param int $lifetime Optional, the specific lifetime for this record
     *                      (by default null, infinite lifetime)
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    public function updateExpireByTag($tag, $lifetime = null)
    {
        $tags = (array)$tag;
        if (0 == count($tags)) {
            return false;
        }

        foreach ($tags as $tag) {
            if (false !== $tagged = $this->load(self::TAGS_PREFIX . $tag)) {
                $update_tagged = array();
                foreach ($tagged as $id) {
                    if (false !== $data = $this->load($id)) {
                        $this->save($id, $data, $lifetime);
                        $update_tagged[] = $id;
                    }
                    $this->save(self::TAGS_PREFIX . $tag, $update_tagged);
                }
            }
        }

        return true;
    }

    /**
     * Returns the minimum expire date time for all cache records
     * associated to one of the provided tags
     * @param  string|array $tag
     * @param int $lifetime Optional, the specific lifetime for this record
     *                      (by default 0, infinite lifetime)
     * @return int
     */
    public function getMinExpireByTag($tag, $lifetime = 0)
    {
        $tags = (array)$tag;

        if (0 == count($tags)) {
            return $lifetime;
        }

        foreach ($tags as $tag) {
            if (false !== $tagged = $this->load(self::TAGS_PREFIX . $tag)) {
                foreach ($tagged as $id) {
                    if (false !== $last_timestamp = $this->test($id)) {
                        if (0 === $lifetime) {
                            $lifetime = $last_timestamp;
                        } elseif (0 !== $last_timestamp) {
                            $lifetime = min(array($last_timestamp, $lifetime));
                        }
                    }
                }
            }
        }

        return $lifetime;
    }

    /**
     * Returns TRUE if the server is already added to Memcache, FALSE otherwise
     * @param string $host
     * @param int $port
     * @return boolean
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if none Memcache object is not initialized
     * @codeCoverageIgnore
     */
    private function _hasServer($host, $port)
    {
        $servers = $this->getServerList();
        foreach ($servers as $server) {
            if ($server['host'] === $host && $server['port'] === $port) {
                return true;
            }
        }

        return false;
    }

    /**
     * Logs error result code and message
     * @param string $method
     * @return boolean
     * @codeCoverageIgnore
     */
    private function _onError($method)
    {
        $this->log('notice', sprintf('Error occured on Memcache::%s().', $method));
        return false;
    }

}
