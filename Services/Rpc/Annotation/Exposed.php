<?php
namespace BackBuilder\Services\Rpc\Annotation;

/**
 * @Annotation
 */
class Exposed
{
    private $_secured;
    
    public function __construct(array $options = array())
    {
        $this->_secured = (isset($options["secured"])) ? $options["secured"] : true;
    }
    
    public function __get($name)
    {
        if ($name === 'secured')
            return $this->{'_' . $name};
        return null;
    }
}