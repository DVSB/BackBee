<?php

namespace BackBuilder\Config\Exception;

/**
 * Exception thrown if a configuration file can not be parse
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class InvalidConfigException extends ConfigException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::UNABLE_TO_PARSE;

}
