<?php

namespace BackBuilder\Config\Exception;

use BackBuilder\Exception\BBException;

/**
 * Configuration exceptions
 *
 * Error codes defined are :
 *
 * * UNABLE_TO_PARSE:  the configuration file can not be parse
 * * INVALID_BASE_DIR: the base directory cannot be read
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
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