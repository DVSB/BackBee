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

namespace BackBee\Util;

class Server
{
    private static $starttime;

    public static function startMicrotime()
    {
        self::$starttime = microtime(true);
    }

    public static function stopMicrotime()
    {
        return number_format(microtime(true) - self::$starttime, 6);
    }

    public static function getPhpMemoryUsage()
    {
        return \BackBee\Importer\Importer::convertMemorySize(memory_get_usage(true));
    }

    public static function getMemoryUsage()
    {
        $free = shell_exec('free');
        $free = (string) trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        $memory_usage = $mem[2] / $mem[1] * 100;

        return $memory_usage;
    }

    public static function getCpuUsage()
    {
        $load = sys_getloadavg();

        return $load[0];
    }
}
