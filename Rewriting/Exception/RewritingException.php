<?php

namespace BackBuilder\Rewriting\Exception;

use BackBuilder\Exception\BBException;

class RewritingException extends BBException
{
    const UNKNOWN_ERROR  = 15000;
    const MISSING_SCHEME = 15001;

    private $_code = self::UNKNOWN_ERROR;    
}