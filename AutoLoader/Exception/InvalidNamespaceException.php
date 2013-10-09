<?php

namespace BackBuilder\AutoLoader\Exception;

/**
 * Exception thrown if the syntax of the namespace is invalid
 *
 * @category    BackBuilder
 * @package     BackBuilder\AutoLoader\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class InvalidNamespaceException extends AutoloaderException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::INVALID_NAMESPACE;

}