<?php

namespace BackBuilder\Exception;

/**
 * Exception thrown if an invalid argument is provided
 *
 * @category    BackBuilder
 * @package     BackBuilder\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class InvalidArgumentException extends BBException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::INVALID_ARGUMENT;

}