<?php
namespace BackBuilder\Util;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @copyright   Lp system
 * @author      n.dufreche
 */
class Fire
{
    public static function log($var)
    {
        $firePHP = \FirePHP::getInstance(true);
        $firePHP->log($var);
    }
}