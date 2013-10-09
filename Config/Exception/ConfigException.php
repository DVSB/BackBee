<?php

namespace BackBuilder\Config\Exception;

use BackBuilder\Exception\BBException;

<<<<<<< HEAD
class ConfigException extends BBException {
	const UNKNOWN_ERROR     = 4000;
	const UNABLE_TO_PARSE   = 4001;
	
	private $_code = self::UNKNOWN_ERROR;
=======
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

>>>>>>> cc066bed9988841e71190dea520f6618f0a3b6ea
}