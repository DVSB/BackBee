<?php

namespace BackBuilder\Cache;

/**
 * Abstract class for cache adapters with extended features
 * as tag and expire date time
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Cache
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
abstract class AExtendedCache extends ACache
{

    /**
     * Removes all cache records associated to one of the tags
     * @param  string|array $tag
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    abstract public function removeByTag($tag);

    /**
     * Updates the expire date time for all cache records 
     * associated to one of the provided tags
     * @param  string|array $tag
     * @param int $lifetime Optional, the specific lifetime for this record 
     *                      (by default null, infinite lifetime)
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    abstract public function updateExpireByTag($tag, $lifetime = null);
}