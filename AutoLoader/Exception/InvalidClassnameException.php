<?php

namespace BackBuilder\AutoLoader\Exception;

/**
 * Exception thrown if the syntax of the class name is invalid
 *
 * @category    BackBuilder
 * @package     BackBuilder\AutoLoader\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class InvalidClassnameException extends AutoloaderException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::INVALID_CLASSNAME;

}