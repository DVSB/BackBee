<?php

namespace BackBuilder\Exception;

/**
 * Exception thrown if none Backbuilder application is available
 *
 * @category    BackBuilder
 * @package     BackBuilder\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class MissingApplicationException extends BBException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::MISSING_APPLICATION;

}