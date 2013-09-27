<?php
namespace BackBuilder\Security\Exception;

use BackBuilder\Exception\BBException;

class SecurityException extends BBException {
    const UNKNOWN_ERROR        = 9000;
    const UNSUPPORTED_TOKEN    = 9001;
    const UNKNOWN_USER         = 9002;
    const INVALID_CREDENTIALS  = 9003;
    const INVALID_KEY          = 9004;
    const EXPIRED_AUTH         = 9005;
    const EXPIRED_TOKEN        = 9006;
    const UNAUTHORIZED_USER    = 9007;
    
    private $_code = self::UNKNOWN_ERROR;
}