<?php

namespace BackBuilder\Security\Exception;

/**
 * Exception thrown if the provided token is not supported
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class UnsupportedTokenException extends SecurityException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::UNSUPPORTED_TOKEN;

}