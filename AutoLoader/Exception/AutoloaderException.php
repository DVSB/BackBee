<?php

namespace BackBuilder\AutoLoader\Exception;

use BackBuilder\Exception\BBException;

/**
 * Autoloader exception thrown if a class can not be load
 *
 * Error codes defined are :
 *
 * * CLASS_NOTFOUND : none file or wrapper found for the given class name
 * * INVALID_OPCODE : the included file or wrapper contains invalid code
 * * INVALID_NAMESPACE : the syntax of the namespace is invalid
 * * INVALID_CLASSNAME : the syntax of the class name is invalid
 * * UNREGISTERED_NAMESPACE : the namespace is not registered
 *
 * @category    BackBuilder
 * @package     BackBuilder\AutoLoader\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class AutoloaderException extends BBException
{

    /**
     * None file or wrapper found for the given class name
     * @var int
     */
    const CLASS_NOTFOUND = 2001;

    /**
     * The included file or wrapper contains invalid code
     * @var int
     */
    const INVALID_OPCODE = 2002;

    /**
     * The syntax of the given namespace is invalid
     * @var int
     */
    const INVALID_NAMESPACE = 2003;

    /**
     * The syntax of the given class name is invalid
     * @var int
     */
    const INVALID_CLASSNAME = 2004;

    /**
     * The given namespace is not registered
     * @var int
     */
    const UNREGISTERED_NAMESPACE = 2005;

}