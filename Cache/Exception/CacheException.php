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

namespace BackBuilder\Cache\Exception;

use BackBuilder\Exception\BBException;

/**
 * Cache exception thrown if a cache adapter can not be initialized
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache
 * @subpackage  Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class CacheException extends BBException
{
    /**
     * Cache adapter can not be intialized
     * @var int
     */

    const CACHE_ERROR = 3001;

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::CACHE_ERROR;

}