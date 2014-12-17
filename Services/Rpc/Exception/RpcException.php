<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Services\Rpc\Exception;

use BackBee\Exception\BBException;

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
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Rpc\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
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
