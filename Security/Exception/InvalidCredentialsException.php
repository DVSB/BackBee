<?php

namespace BackBuilder\Security\Exception;

/**
 * Exception thrown if the provided credentials do not match
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class InvalidCredentialsException extends SecurityException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::INVALID_CREDENTIALS;

}