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

namespace BackBee\Bundle\Exception;

use BackBee\Exception\BBException;

/**
 * Exception thrown if a bundle can not be loaded, init, started or ran
 *
 * @category    BackBee
 * @package     BackBee\Bundle
 * @subpackage  Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class BundleException extends BBException
{
    const UNKNOWN_ERROR = 20000;
    const INIT_ERROR = 20001;
    const START_ERROR = 20002;
    const RUN_ERROR = 20003;

    protected $_code = self::UNKNOWN_ERROR;
}
