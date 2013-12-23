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

use BackBuilder\Cache\AExtendedCache,
    BackBuilder\Cache\Exception\CacheException;

/**
 * MemCached cache adapter
 * 
 * It supports tag and expire features
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache
 * @subpackage  MemCache
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Memcached extends AExtendedCache
{

    /**
     * Default memcached host
     * @var string
     */
    const DEFAULT_HOST = '127.0.0.1';

    /**
     * Default memcached server port
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
     * Memcached adapter options
     * @var array
     */
    protected $_instance_options = array(
        'servers' => array(),
        'options' => array()
    );

    /**
     * The Memcached object
     * @var \Memcached 
     */
    private $_memcached;

    /**
     * The default memcached server connection information
     * @var array
     */
    private $_default_server = array(
        'host' => self::DEFAULT_HOST,
        'port' => self::DEFAULT_PORT,
        'weight' => self::DEFAULT_WEIGHT
    );

    /**
     * Override some default Memcached instance options
     * @var array
     */
    private $_memcached_options = array(
        2 => 1, // OPT_HASH = HASH_MD5
        9 => 1, // OPT_DISTRIBUTION = DISTRIBUTION_CONSISTENT
        16 => true // OPT_LIBKETAMA_COMPATIBLE = true
    );

    /**
     * Class constructor
     * @param array $options Initial options for the cache adapter:
     *                         - servers array The memcached servers to add
     *                         - options array The memcached options to set
     * @param \Psr\Log\LoggerInterface $logger An optional logger
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if Memcached extension is not available.
     */
    public function __construct(array $options = array(), \Psr\Log\LoggerInterface $logger = null)
    {
        if (false === extension_loaded('memcached')) {
            throw new CacheException('Memcached extension is not available');
        }

        $this->_memcached = new \Memcached();

        parent::__construct($options, $logger);
    }

    /**
     * Sets the memcache adapter instance options
     * @param array $options
     * @return \BackBuilder\Cache\MemCache\Memcached
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if a provided option is unknown for this adapter.
     */
    protected function setInstanceOptions(array $options = array())
    {
        parent::setInstanceOptions($options);

        if (false === is_array($this->_instance_options['options'])) {
            throw new CacheException('Memcached adapter: memcached options is not an array.');
        }

        $this->_instance_options['options'] = array_merge($this->_memcached_options, $this->_instance_options['options']);
        foreach ($this->_instance_options['options'] as $option => $value) {
            $this->setOption($option, $value);
        }

        if (false === is_array($this->_instance_options['servers'])) {
            throw new CacheException('Memcached adapter: memcached servers is not an array.');
        }

        $this->addServers($this->_instance_options['servers']);

        return $this;
    }

    /**
     * Adds a server to the server pool
     * @param string $host  The hostname of the memcache server
     * @param int $port     The port on which memcache is running, 11211 by default
     * @param int $weight   The weight of the server
     * @return boolean      TRUE on success or FALSE on failure.
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if none memcached object is not initialized
     * @link http://php.net/manual/en/memcached.addserver.php
     */
    public function addServer($host, $port, $weight = 0)
    {
        if (false === $this->_isMemcachedAvailable()) {
            throw new CacheException('Memcached object is not initialized');
        }

        if (true === $this->_hasServer($host, $port)) {
            return true;
        }

        if (false === $this->_memcached->addServer($host, $port, $weight)) {
            return $this->_onError('setOption');
        }

        return true;
    }

    /**
     * Adds a set of servers to the memcache instance pool
     * @param array $servers
     * @return boolean
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if there is a server configuration is not an array
     * @link http://php.net/manual/en/memcached.addservers.php
     */
    public function addServers(array $servers = array())
    {
        $result = true;

        foreach ($servers as $server) {
            if (false === is_array($server)) {
                throw new CacheException('Memcached adapter: server configuration is not an array.');
            }

            $server = array_merge($this->_default_server, $server);
            $result = $result && $this->addServer($server['host'], $server['port'], $server['weight']);
        }

        return $result;
    }

    /**
     * Sets memcached option
     * @param int $option
     * @param mixed $value
     * @return boolean
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if none memcached object is not initialized
     * @link http://php.net/manual/en/memcached.setoption.php
     */
    public function setOption($option, $value)
    {
        if (false === $this->_isMemcachedAvailable()) {
            throw new CacheException('Memcached object is not initialized');
        }

        if (false === is_int($option)) {
            $this->log('warning', sprintf('Unknown memcached option: `%s`.', $option));
            return false;
        }

        if (false === $this->_memcached->setOption($option, $value)) {
            return $this->_onError('setOption');
        }

        return true;
    }

    /**
     * Gets memcached option
     * @param mixed $option
     * @param mixed $value
     * @return boolean
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if none memcached object is not initialized
     * @link http://php.net/manual/en/memcached.getoption.php
     */
    public function getOption($option)
    {
        if (false === $this->_isMemcachedAvailable()) {
            throw new CacheException('Memcached object is not initialized');
        }

        if (false === is_int($option)) {
            $this->log('warning', sprintf('Unknown memcached option: `%s`.', $option));
            return false;
        }

        if (false === $this->_memcached->getOption($option)) {
            return $this->_onError('getOption');
        }

        return true;
    }

    /**
     * Returns the list of available memcached servers
     * @return array
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if none memcached object is not initialized
     * @link http://php.net/manual/en/memcached.getserverlist.php
     * @codeCoverageIgnore
     */
    public function getServerList()
    {
        if (false === $this->_isMemcachedAvailable()) {
            throw new CacheException('Memcached object is not initialized');
        }

        return $this->_memcached->getServerList();
    }

    /**
     * Returns the result code of the last operation
     * @return int Result code of the last Memcached operation.
     * @link http://php.net/manual/en/memcached.getresultcode.php
     * @codeCoverageIgnore
     */
    public function getResultCode()
    {
        return $this->_memcached->getResultCode();
    }

    /**
     * Return the message describing the result of the last operation
     * @return string Message describing the result of the last Memcached operation.
     * @link http://php.net/manual/en/memcached.getresultmessage.php
     * @codeCoverageIgnore
     */
    public function getResultMessage()
    {
        return $this->_memcached->getResultMessage();
    }

    /**
     * Returns the available cache for the given id if found returns FALSE else
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
                || false === $last_timestamp
                || $expire->getTimestamp() <= $last_timestamp) {

            if (false === $tmp = $this->_memcached->get($id)) {
                return $this->_onError('load');
            }

            if (true === is_array($tmp)) {
                return $tmp[0];
            }
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
        if (false === $this->_isMemcachedAvailable()) {
            return false;
        }

        if (false === $tmp = $this->_memcached->get($id)) {
            return false;
        }

        if (2 === count($tmp)) {
            return (0 === $tmp[1] || time() < (int) $tmp[1]);
        }

        return false;
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
    public function save($id, $data, $lifetime = null, $tag = null)
    {
        if (false === $this->_isMemcachedAvailable()) {
            return false;
        }

        $expire = $this->_getExpireDateTime($lifetime);
        if (false === $this->_memcached->set($id, array($data, $expire), $expire)) {
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
        if (false === $this->_isMemcachedAvailable()) {
            return false;
        }

        if (false === $this->_memcached->delete($id)) {
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
        if (false === $this->_isMemcachedAvailable()) {
            return false;
        }

        if (false === $this->_memcached->flush()) {
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
        if (false === $this->_isMemcachedAvailable()) {
            return false;
        }

        $tags = (array) $tag;
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
        if (false === $this->_isMemcachedAvailable()) {
            return false;
        }

        $tags = (array) $tag;
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
     * Returns TRUE if MemCached object is defined and available, FALSE otherwise
     * @return boolean
     * @codeCoverageIgnore
     */
    private function _isMemcachedAvailable()
    {
        return ($this->_memcached instanceof \Memcached);
    }

    /**
     * Returns TRUE if the server is already added to Memcached, FALSE otherwise
     * @param string $host
     * @param int $port
     * @return boolean
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if none memcached object is not initialized
     * @codeCoverageIgnore
     */
    private function _hasServer($host, $port)
    {
        if (false === $this->_isMemcachedAvailable()) {
            throw new CacheException('Memcached object is not initialized');
        }

        $servers = $this->_memcached->getServerList();
        foreach ($servers as $server) {
            if ($server['host'] === $host && $server['port'] === $port) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the expiration timestamp
     * @param int $lifetime
     * @return int
     * @codeCoverageIgnore
     */
    private function _getExpireDateTime($lifetime = null)
    {
        $expire = 0;

        if (null !== $lifetime && 0 !== $lifetime) {
            $expire = new \DateTime ();

            if (0 < $lifetime) {
                $expire->add(new \DateInterval('PT' . $lifetime . 'S'));
            } else {
                $expire->sub(new \DateInterval('PT' . (-1 * $lifetime) . 'S'));
            }

            return $expire->getTimestamp();
        }

        return $expire;
    }

    /**
     * Logs error result code and message
     * @param string $method
     * @return boolean
     * @codeCoverageIgnore
     */
    private function _onError($method)
    {
        if (true === $this->_isMemcachedAvailable()) {
            $this->log('warning', sprintf('Error occured on Memcached::%s(): [%s] %s.', $method, $this->_memcached->getResultCode(), $this->_memcached->getResultMessage()));
        }

        return false;
    }

}