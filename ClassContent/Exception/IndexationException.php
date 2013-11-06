<?php

namespace BackBuilder\ClassContent\Exception;

use BackBuilder\Exception\BBException;

/**
 * An indexation exception
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class IndexationException extends BBException
{

    const UNKNOWN_ERROR = 10000;

    protected $_code = self::UNKNOWN_ERROR;

}