<?php

namespace BackBuilder\AutoLoader\Exception;

/**
 * Exception thrown if none file or wrapper found for the given class name
 *
 * @category    BackBuilder
 * @package     BackBuilder\AutoLoader\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class ClassNotFoundException extends AutoloaderException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::CLASS_NOTFOUND;

}