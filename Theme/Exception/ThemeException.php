<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\Theme\Exception;

use BackBee\Exception\BBException;

/**
 * @category    BackBee
 * @package     BackBee\Theme
 * @subpackage  Exception
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class ThemeException extends BBException
{
    const UNKNOWN_ERROR = 12000;
    const THEME_PATH_INCORRECT = 12001;
    const THEME_BAD_CONSTRUCT = 12001;
    const THEME_NOT_FOUND = 12003;
    const THEME_CONFIG_INCORRECT = 12004;
    const THEME_ALREADY_EXISTANT = 12005;

    protected $_code = self::UNKNOWN_ERROR;
}
