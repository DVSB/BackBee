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

namespace BackBuilder\Cache;

use BackBuilder\Cache\Exception\CacheException,
    BackBuilder\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Abstract class for cache adapters
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
abstract class ACache
{

    /**
     * Cache adapter options
     * @var array
     */
    protected $_instance_options = array();

    /**
     * A logger
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger = null;

    /**
     * Class constructor
     * @param array $options An array of options allowing to construct the cache adapter
     * @param \Psr\Log\LoggerInterface $logger An optional logger
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if the cache adapter cannot be construct
     * @codeCoverageIgnore
     */
    public function __construct(array $options = array(), LoggerInterface $logger = null)
    {
        $this->setLogger($logger)
                ->setInstanceOptions($options);
    }

    /**
     * Returns the available cache for the given id if found returns FALSE else
     * @param string $id Cache id
     * @param boolean $bypassCheck Allow to find cache without test it before
     * @param \DateTime $expire Optionnal, the expiration time (now by default)
     * @return string|FALSE
     */
    abstract public function load($id, $bypassCheck = false, \DateTime $expire = null);

    /**
     * Tests if a cache is available or not (for the given id)
     * @param string $id Cache id
     * @return int|FALSE the last modified timestamp of the available cache record
     */
    abstract public function test($id);

    /**
     * Saves some string datas into a cache record
     * @param string $id Cache id
     * @param string $data Datas to cache
     * @param int $lifetime Optional, the specific lifetime for this record 
     *                      (by default null, infinite lifetime)
     * @param string $tag Optional, an associated tag to the data stored
     * @return boolean TRUE if cache is stored FALSE otherwise
     */
    abstract public function save($id, $data, $lifetime = null, $tag = null);

    /**
     * Removes a cache record
     * @param  string $id Cache id
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    abstract public function remove($id);

    /**
     * Clears all cache records
     * @return boolean TRUE if cache is cleared FALSE otherwise
     */
    abstract public function clear();

    /**
     * Sets the cache logger
     * @param \Psr\Log\LoggerInterface $logger
     * @return \BackBuilder\Cache\ACache
     * @codeCoverageIgnore
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->_logger = $logger;
        return $this;
    }

    /**
     * Sets the cache adapter instance options
     * @param array $options
     * @return \BackBuilder\Cache\ACache
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if a provided option is unknown for this adapter.
     */
    protected function setInstanceOptions(array $options = array())
    {
        foreach ($options as $key => $value) {
            if (true === array_key_exists($key, $this->_instance_options)) {
                $this->_instance_options[$key] = $value;
            } else {
                throw new CacheException(sprintf('Unknown option %s for cache adapter %s.', $key, get_class($this)));
            }
        }

        return $this;
    }

    /**
     * Logs a message on provided level if a logger is defined
     * @param string $method The log level
     * @param string $message The message to log
     * @param array $context The logging context
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if the defined logger does not implement \Psr\Log\LoggerInterface
     * @codeCoverageIgnore
     */
    protected function log($level, $message, array $context = array())
    {
        if (null !== $this->_logger) {
            if (false === ($this->_logger instanceof LoggerInterface)) {
                throw new InvalidArgumentException('Logger must implements \Psr\Log\LoggerInterface');
            }

            $this->_logger->log($level, $message, $context);
        }
    }

}