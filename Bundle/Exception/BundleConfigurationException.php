<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Bundle\Exception;

use BackBee\Exception\BBException;

/**
 * Exception thrown if a bundle can not be loaded, init, started or ran.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class BundleConfigurationException extends BBException
{
    const BUNDLE_UNDECLARED = 21000;
    const CONTROLLER_SECTION_MISSING = 21001;
    const CONTROLLER_UNDECLARED = 21002;
    const ADMIN_ROUTE_BADLY_INVOKED = 21003;

    protected $_code = self::UNKNOWN_ERROR;
}
