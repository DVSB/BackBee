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

namespace BackBee\Services\Exception;

use BackBee\Exception\BBException;

/**
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class ServicesException extends BBException
{
    const UNKNOWN_ERROR = 7000;
    const UNDEFINED_APP = 7001;
    const UNDEFINED_SITE = 7002;
    const UNAUTHORIZED_USER = 7003;
    const CONTENT_OUTOFDATE = 7100;

    protected $_code = self::UNKNOWN_ERROR;
}
