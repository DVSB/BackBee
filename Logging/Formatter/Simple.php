<?php
namespace BackBuilder\Logging\Formatter;

class Simple implements IFormatter {
    private $_format = '%d %p [%u]: %m';
    
    public function __construct($format = NULL) {
        if (NULL !== $format)
            $this->_format = $format;
    }
    
    public function format($event) {
        $output = $this->_format.PHP_EOL;
        
        foreach($event as $key => $value)
            $output = str_replace('%'.$key, $value, $output);
        
        return $output;
    }
}