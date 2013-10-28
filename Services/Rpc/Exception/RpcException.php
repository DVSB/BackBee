<?php

namespace BackBuilder\Services\Rpc\Exception;

use BackBuilder\Exception\BBException;

/**
 * RPC exception thrown if a RPC request cannot be handled
 *
 * Error codes defined are :
 *
 * * INVALID_REQUEST : the RPC request is invalid
 * * INVALID_METHOD : invalid method request
 * * MALFORMED_PAYLOAD : the payload is malformed
 * * UNDEFINED_LAYOUT : none layout defined
 *
 * @category    BackBuilder
 * @package     BackBuilder\Services\Rpc\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class RpcException extends BBException
{
    /**
     * The RPC request is invalid
     * @var int
     */
    const INVALID_REQUEST = 11001;
    
    /**
     * Invalid method request
     * @var int
     */
    const INVALID_METHOD = 11002;
    
    /**
     * Malformed payload data
     * @var int
     */
    const MALFORMED_PAYLOAD = 11003;
    
    /**
     * Undefined layout
     * @vat int
     */
    const UNDEFINED_LAYOUT = 11004;
    
}
