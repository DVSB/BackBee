<?php
namespace BackBuilder\Theme\Exception;

use BackBuilder\Exception\BBException;

class ThemeException extends BBException {
    const UNKNOWN_ERROR = 12000;
    const THEME_PATH_INCORRECT = 12001;
    const THEME_BAD_CONSTRUCT = 12001;
    const THEME_NOT_FOUND = 12003;
    const THEME_CONFIG_INCORRECT = 12004;
    const THEME_ALREADY_EXISTANT = 12005;


    private $_code = self::UNKNOWN_ERROR;
}