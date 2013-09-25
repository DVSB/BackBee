<?php

namespace BackBuilder\Cache;

use BackBuilder\BBApplication;

/**
 * Abstract class for cache adapters
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
abstract class ACache
{

    /**
     * Class constructor
     * @param \BackBuilder\BBApplication $application Optionnal BackBuilder Application
     */
    abstract public function __construct(BBApplication $application);

    /**
     * Returns the available cache for the given id if found returns false else
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
     * Save some string datas into a cache record
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
}