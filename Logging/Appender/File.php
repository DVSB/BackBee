<?php
namespace BackBuilder\Logging\Appender;

use BackBuilder\Logging\Formatter\IFormatter,
    BackBuilder\Logging\Formatter\Simple,
    BackBuilder\Logging\Exception\LoggingException;

class File implements IAppender {
    private $_fhandler = NULL;
    private $_formatter = NULL;
    
    public function __construct($options) {
        if (!array_key_exists('logfile', $options))
            throw new LoggingException('None log file specified');
        
        $logfile = $options['logfile'];
        $dirname = dirname($logfile);
        $mode = array_key_exists('mode', $options) ? $options['mode'] : 'a';
        
        if ('' == $dirname || !is_dir($dirname)) {
            $r = new \ReflectionObject($this);
            $logfile = dirname($r->getFileName()).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR . '..'.DIRECTORY_SEPARATOR.
                       str_replace('/', DIRECTORY_SEPARATOR, $logfile);
            $dirname = dirname($logfile);
        }
        
        if (!is_dir($dirname) && !@mkdir($dirname, 0711, TRUE))
            throw new LoggingException(sprintf('Unable to create log directory `%s`.', $dirname));
        
        if (! $this->_fhandler = @fopen($logfile, $mode, false)) {
            throw new LoggingException(sprintf('Unable to open the file `%s` with mode `%s`.', $logfile, $mode));
        }
        
        $this->setFormatter(new Simple());
    }
    
    public function close() {
        if (is_resource($this->_fhandler))
            fclose($this->_fhandler);
    }
    
    public function setFormatter(IFormatter $formatter) {
        $this->_formatter = $formatter;
        return $this;
    }
    
    public function write($event) {
        $log = $this->_formatter->format($event);
        
        if (FALSE === @fwrite($this->_fhandler, $log))
            throw new LoggingException('Unable to write log entry.');
    }
}