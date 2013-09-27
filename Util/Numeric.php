<?php

namespace BackBuilder\Util;

/**
 * Set of utility methods to deal with numeric varaibles
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class Numeric
{

    /**
     * Checks if the variable can be cast to an integer
     * @param mixed $var The variable to test
     * @return Boolean TRUE if the variable can be cast to integer, FALSE otherwise
     */
    public static function isInteger($var)
    {
        return is_numeric($var) && (string) ((int) $var) === (string) $var;
    }

    /**
     * Checks if the variable can be cast to a positive integer
     * @param mixed $var The variable to test
     * @param type $strict Optional, if TRUE (default) checks for a strictly positive value
     * @return Boolean TRUE if the variable can be cast to a positive integer, FALSE otherwise
     */
    public static function isPositiveInteger($var, $strict = true)
    {
        return self::isInteger($var) && (true === $strict ? (int) $var > 0 : (int) $var >= 0);
    }

}