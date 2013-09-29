<?php

namespace BackBuilder\Services\Rpc\Exception;

/**
 * Exception thrown if the RPC request is invalid
 *
 * @category    BackBuilder
 * @package     BackBuilder\Services\Rpc\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class InvalidRequestException extends RpcException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::INVALID_REQUEST;

}