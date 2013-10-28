<?php

namespace BackBuilder\Logging\Exception;

use BackBuilder\Exception\BBException;

/**
 * Logging exception thrown if a message cannot be log
 *
 * Error codes defined are :
 *
 * * UNKNOWN_LEVEL : the log level asked is unknown
 *
 * @category    BackBuilder
 * @package     BackBuilder\Logging\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class LoggingException extends BBException
{

    /**
     * The log level asked is unknown
     * @var int
     */
    const UNKNOWN_LEVEL = 10001;

}