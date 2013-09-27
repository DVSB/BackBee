<?php

namespace BackBuilder\Security\Exception;

/**
 * Exception thrown if token expired
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class ExpiredTokenException extends SecurityException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::EXPIRED_TOKEN;

}