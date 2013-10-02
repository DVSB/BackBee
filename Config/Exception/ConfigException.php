<?php
namespace BackBuilder\Config\Exception;

use BackBuilder\Exception\BBException;

class ConfigException extends BBException {
	const UNKNOWN_ERROR     = 4000;
	const UNABLE_TO_PARSE   = 4001;
	
	private $_code = self::UNKNOWN_ERROR;
}