<?php

namespace BackBuilder\Cache\Exception;

use BackBuilder\Exception\BBException;

/**
 * Cache exception thrown if a cache adapter can not be initialized
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
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