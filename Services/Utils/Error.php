<?php
namespace BackBuilder\Services\Utils;

/**
 * Description of Error
 *
 * @author m.baptista
 */
class Error {
    const EXCEPTION_CODE                = 1000;
    const BB_EXCEPTION_CODE             = 1001;
    const RCP_EXCEPTION_CODE            = 1002;
    const UPLOAD_EXCEPTION_CODE         = 1003;
    const AUTH_EXCEPTION_CODE           = 1004;
    const REFLECTION_EXCEPTION_CODE     = 1005;
    const AUTOLOAD_EXCEPTION_CODE       = 1006;
    
    public $type = null;
    public $code = null;
    public $message = null;
    
    public function __construct(\Exception $e) {
        $hierarchy = explode(NAMESPACE_SEPARATOR, get_class($e));
        $this->type = array_pop($hierarchy);
        $this->code = $e->getCode();
        $this->message = $e->getMessage();
        $this->trace = $e->getTrace();
    }
}