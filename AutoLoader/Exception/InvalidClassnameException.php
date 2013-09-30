<?php

namespace BackBuilder\AutoLoader\Exception;

/**
 * Exception thrown if the syntax of the class name is invalid
 *
 * @category    BackBuilder
 * @package     BackBuilder\AutoLoader\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class InvalidClassnameException extends AutoloaderException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::INVALID_CLASSNAME;

}
