<?php
namespace BackBuilder\Installer\Annotation;

/**
 * @Annotation
 */
class Fixture
{
    private $_values;

    public function __construct(array $options = array())
    {
        $this->_values = (object)$options;
    }
    
    public function getFixture()
    {
        $fixture = '$this->faker->' . $this->_values->type;
        if (property_exists($this->_values, 'value')) {
            $fixture .= '(' . $this->_values->value . ')';
        }
        return $fixture . ';';
    }
    
    public function getType()
    {
        return function() use ($generator) { return $generator->{$this->_values->type}; };
    }

    public function __get($name)
    {
        if (property_exists($this->_values, $name)) {
            return $this->_values->{$name};
        }
        return false;
    }

    public function __isset($name)
    {
        return property_exists($this->_values, $name);
    }
}