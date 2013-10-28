<?php

namespace BackBuilder\AutoLoader\Exception;

/**
 * Exception thrown if the namespace is not registered
 *
 * @category    BackBuilder
 * @package     BackBuilder\AutoLoader\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class UnregisteredNamespaceException extends AutoloaderException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::UNREGISTERED_NAMESPACE;

}