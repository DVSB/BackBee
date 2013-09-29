<?php

namespace BackBuilder\Services\Rpc\Exception;

/**
 * Exception thrown if none layout is defined
 *
 * @category    BackBuilder
 * @package     BackBuilder\Services\Rpc\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class UndefinedLayoutException extends RpcException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::UNDEFINED_LAYOUT;

}