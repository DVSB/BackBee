<?php
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
