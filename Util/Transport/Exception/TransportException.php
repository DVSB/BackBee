<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\Util\Transport\Exception;

use BackBuilder\Exception\BBException;

/**
 * Transport exception thrown on connection and authentication
 *
 * Error codes defined are :
 *
 * * UNKNOWN_ERROR : unregistered exception
 * * MISCONFIGURATION : the provided options are invalid
 * * CONNECTION_FAILED : unable to connect to the remote server
 * * AUTHENTICATION_FAILED : unable to authenticate on the remote server
 *
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @subpackage  Transport\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class TransportException extends BBException
{
    /**
     * Unregistered exception
     * @var int
     */
    const UNKNOWN_ERROR = 10000;

    /**
     * Misconfiguration
     * @var int
     */
    const MISCONFIGURATION = 10001;

    /**
     * Connection failed
     * @var int
     */
    const CONNECTION_FAILED = 10002;

    /**
     * Authentication failed
     * @var int
     */
    const AUTHENTICATION_FAILED = 10003;
}
