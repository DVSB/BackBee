<?php

namespace BackBuilder\Exception;

/**
 * Exception thrown if an invalid argument is provided
 *
 * @category    BackBuilder
 * @package     BackBuilder\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class InvalidArgumentsException extends BBException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::INVALID_ARGUMENT;

}