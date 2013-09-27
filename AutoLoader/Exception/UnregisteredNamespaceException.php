<?php

namespace BackBuilder\AutoLoader\Exception;

/**
 * Exception thrown if the namespace is not registered
 *
 * @category    BackBuilder
 * @package     BackBuilder\AutoLoader\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class UnregisteredNamespaceException extends AutoloaderException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::UNREGISTERED_NAMESPACE;

}