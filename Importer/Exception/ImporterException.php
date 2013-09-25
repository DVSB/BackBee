<?php
namespace BackBuilder\Importer\Exception;

use BackBuilder\Exception\BBException;

class ImporterException extends BBException {
    const UNKNOWN_ERROR       = 30000;
    const INIT_ERROR          = 30001;
    const RUN_ERROR           = 30002;
    
    private $_code = self::UNKNOWN_ERROR;
}