<?php

namespace BackBuilder\Security\Exception;

/**
 * Exception thrown if the provided username does not match a UserInterface instance
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class UnknownUserException extends SecurityException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::UNKNOWN_USER;

}