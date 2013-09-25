<?php
namespace BackBuilder\Bundle\Exception;

use BackBuilder\Exception\BBException;

class BundleException extends BBException {
    const UNKNOWN_ERROR       = 20000;
    const INIT_ERROR          = 20001;
    const START_ERROR         = 20002;
    const RUN_ERROR           = 20003;
    
    private $_code = self::UNKNOWN_ERROR;
}