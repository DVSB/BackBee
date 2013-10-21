<?php

namespace BackBuilder\Security\Exception;

use BackBuilder\Exception\BBException;

/**
 * Security Exception
 *
 * Error codes defined are :
 *
 * * UNSUPPORTED_TOKEN : the provided token is not supported
 * * UNKNOWN_USER : the provided username does not match a UserInterface instance
 * * INVALID_CREDENTIALS : the provided credentials do not match
 * * INVALID_KEY : the provided key does not match the firewall key
 * * EXPIRED_AUTH : authentication expired
 * * EXPIRED_TOKEN : token expired
 * * FORBIDDEN_ACCESS : user does not have enough privileges
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class SecurityException extends BBException
{

    /**
     * The provided token is not supported
     * @var int
     */
    const UNSUPPORTED_TOKEN = 9001;

    /**
     * The provided username does not match a UserInterface instance
     * @var int
     */
    const UNKNOWN_USER = 9002;

    /**
     * The provided credentials do not match
     * @var int
     */
    const INVALID_CREDENTIALS = 9003;

    /**
     * The provided key does not match the firewall key
     * @var int
     */
    const INVALID_KEY = 9004;

    /**
     * Authentication expired
     * @var int
     */
    const EXPIRED_AUTH = 9005;

    /**
     * Token expired
     * @var int
     */
    const EXPIRED_TOKEN = 9006;

    /**
     * User does not have enough privileges
     * @var int
     */
    const FORBIDDEN_ACCESS = 9007;

}