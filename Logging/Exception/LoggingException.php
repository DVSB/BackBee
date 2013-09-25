<?php
namespace BackBuilder\Logging\Exception;

use BackBuilder\Exception\BBException;

class LoggingException extends BBException {
    const UNKNOWN_ERROR    = 10000;
    
    private $_code = self::UNKNOWN_ERROR;
}