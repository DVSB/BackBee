<?php
namespace BackBuilder\Renderer\Exception;

use BackBuilder\Exception\BBException;

class RendererException extends BBException {
    const UNKNOWN_ERROR    = 5000;
    const SCRIPTFILE_ERROR = 5001;
    const RENDERING_ERROR  = 5002;
    const LAYOUT_ERROR     = 5003;
    const HELPER_ERROR     = 5004;
    
    private $_code = self::UNKNOWN_ERROR;
}