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
namespace BackBee\Config\Exception;

use BackBee\Exception\BBException;

/**
 * Configuration exceptions
 *
 * Error codes defined are :
 *
 * * UNABLE_TO_PARSE:  the configuration file can not be parse
 * * INVALID_BASE_DIR: the base directory cannot be read
 *
 * @category    BackBee
 * @package     BackBee\Config
 * @subpackage  Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class ConfigException extends BBException
{
    /**
     * The configuration file can not be parse
     * @var int
     */
    const UNABLE_TO_PARSE = 4001;

    /**
     * The base directory cannot be read
     * @var int
     */
    const INVALID_BASE_DIR = 4002;
}
