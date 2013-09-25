<?php
namespace BackBuilder\Services\Rpc\Annotation;

/**
 * @Annotation
 */
class Restrict
{
    private $_roles;
    
    public function __construct(array $options = array())
    {
        $this->_roles = (isset($options["roles"])) ? explode(', ', $options["roles"]): null;
    }
    
    public function __get($name)
    {
        if ($name === 'roles')
            return $this->{'_' . $name};
        return null;
    }
}