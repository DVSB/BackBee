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

namespace BackBee\Util\Doctrine;

use Doctrine\DBAL\Driver;

/**
 * Utility class to know supported features by the current driver.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class DriverFeatures
{
    /*
     * Drivers array supporting REPLACE command
     * @var array
     */

    static private $replaceDrivers = array(
        'Doctrine\DBAL\Driver\PDOMySql\Driver',
        'Doctrine\DBAL\Driver\Mysqli\Driver',
        'Doctrine\DBAL\Driver\PDOSqlite\Driver',
    );

    /**
     * Drivers array supporting multi valuated insertions
     * @var array
     */
    static private $multiValuesDrivers = array(
        'Doctrine\DBAL\Driver\PDOMySql\Driver',
        'Doctrine\DBAL\Driver\Mysqli\Driver',
    );

    /**
     * Returns TRUE if the driver support REPLACE comand.
     *
     * @param \Doctrine\DBAL\Driver $driver
     *
     * @return boolean
     */
    public static function replaceSupported(Driver $driver)
    {
        return in_array(get_class($driver), self::$replaceDrivers);
    }

    /**
     * Returns TRUE if the driver support multi valuated nsertions comand
     * @param  \Doctrine\DBAL\Driver $driver
     * @return boolean
     */
    public static function multiValuesSupported(Driver $driver)
    {
        return in_array(get_class($driver), self::$multiValuesDrivers);
    }
}
