<?php

namespace BackBuilder\Security\Exception;

/**
 * Exception thrown if the provided key does not match the firewall key
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class InvalidKeyException extends SecurityException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::INVALID_CREDENTIALS;

}