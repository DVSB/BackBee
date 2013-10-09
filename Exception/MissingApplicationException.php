<?php

namespace BackBuilder\Exception;

/**
 * Exception thrown if none Backbuilder application is available
 *
 * @category    BackBuilder
 * @package     BackBuilder\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class InvalidArgumentException extends BBException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::MISSING_APPLICATION;

}