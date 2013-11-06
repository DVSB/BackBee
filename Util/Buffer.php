<?php

namespace BackBuilder\Util;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @copyright   Lp system
 * @author      n.dufreche
 */
class Buffer
{

    public static function flush()
    {
        if (!ob_get_contents()) {
            ob_start();
        }
        ob_end_flush();
        flush();
        ob_start();
    }

    /**
     * pring the string value directly
     * @param string $string
     * @codeCoverageIgnore
     */
    public static function dump($string)
    {
        print $string;
        static::flush();
    }

}