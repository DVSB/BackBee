<?php

namespace BackBuilder\Logging\Exception;

/**
 * Exception thrown if the log level asked is unknown
 *
 * @category    BackBuilder
 * @package     BackBuilder\Logging\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class UnknownLevelException extends LoggingException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::UNKNOWN_LEVEL;

}