<?php
namespace BackBuilder\Services\Exception;

use BackBuilder\Exception\BBException;

class ServicesException extends BBException {
    const UNKNOWN_ERROR          = 7000;
    const UNDEFINED_APP          = 7001;
    const UNDEFINED_SITE         = 7002;
    const UNAUTHORIZED_USER      = 7003;
    const CONTENT_OUTOFDATE      = 7100;
    
    private $_code = self::UNKNOWN_ERROR;
}