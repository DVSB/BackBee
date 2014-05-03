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

/**
 * Abstract class for cache adapters with extended features
 * as tag and expire date time
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Cache
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
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
