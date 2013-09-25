<?php
namespace BackBuilder\Services\Rpc\Annotation;

/**
 * @Annotation
 */
class Log
{
    private $_entity;
    private $_param;
    
    public function __construct(array $options = array())
    {
        $this->_entity = (isset($options["entity"])) ? $options["entity"]: null;
        $this->_param = (isset($options["param"])) ? $options["param"]: null;
    }
    
    public function __get($name)
    {
        if ($name === 'entity' || $name === 'param')
            return $this->{'_' . $name};
        return null;
    }
}