<?php

namespace BackBuilder\AutoLoader\Exception;

/**
 * Exception thrown if the included file or wrapper contains invalid code
 *
 * @category    BackBuilder
 * @package     BackBuilder\AutoLoader\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class SyntaxErrorException extends AutoloaderException
{

    /**
     * The default error code
     * @var int
     */
    private $_code = self::INVALID_OPCODE;

}