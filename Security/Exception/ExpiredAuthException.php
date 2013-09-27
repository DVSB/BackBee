<?php

namespace BackBuilder\Security\Exception;

/**
 * Exception thrown if authentication expired
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class ExpiredAuthException extends SecurityException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::EXPIRED_AUTH;

}