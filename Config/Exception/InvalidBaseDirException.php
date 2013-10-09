<?php

namespace BackBuilder\Config\Exception;

/**
 * Exception thrown if the base directory can not be read
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class InvalidBaseDirException extends ConfigException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::INVALID_BASE_DIR;

}
