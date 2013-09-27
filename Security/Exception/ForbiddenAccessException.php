<?php

namespace BackBuilder\Security\Exception;

/**
 * Exception thrown if the user does not have enough privileges
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class ForbiddenAccessException extends SecurityException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::FORBIDDEN_ACCESS;

}