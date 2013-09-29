<?php

namespace BackBuilder\Services\Rpc\Exception;

/**
 * Exception thrown if the payload data is malformed
 *
 * @category    BackBuilder
 * @package     BackBuilder\Services\Rpc\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class MalformedPayloadException extends RpcException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::MALFORMED_PAYLOAD;

}