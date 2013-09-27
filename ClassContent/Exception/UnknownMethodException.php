<?php

namespace BackBuilder\ClassContent\Exception;

/**
 * Exception thrown if the method does not exist for the content
 *
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class UnknownMethodException extends ClassContentException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::UNKNOWN_METHOD;

}