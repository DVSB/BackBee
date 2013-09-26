<?php
namespace BackBuilder\Routing;

use Symfony\Component\Routing\Route as sfRoute;

class Route extends sfRoute {
    /**
     * Part of requirements related to headers
     * @var array
     */
    private $_headerRequirements;
    
    /**
     * Constructor.
     *
     * Available requirements:
     *  - HTTP_<<headername>> : HTTP header value required
     *
     * @param string $pattern       The pattern to match
     * @param array  $defaults      An array of default parameter values
     * @param array  $requirements  An array of requirements for parameters (regexes)
     * @param array  $options       An array of options
     */
    public function __construct($pattern, array $defaults = array(), array $requirements = array(), array $options = array()) {
        parent::__construct($pattern, $defaults, $requirements, $options);
        
        $this->_addHeaderRequirements();
    }

    /**
     * Extract header requirements.
     *
     * @return Route The current Route instance
     */
    private function _addHeaderRequirements()
    {
        $this->_headerRequirements = array();
        foreach($this->getRequirements() as $key => $value) {
            if (0 === strpos($key, 'HTTP-')) $this->_headerRequirements[substr($key, 5)] = $value;
        }
        
        return $this;
    }
    
    /**
     * Adds requirements.
     *
     * This method implements a fluent interface.
     *
     * @codeCoverageIgnore
     * @param array $requirements The requirements
     * @return Route The current Route instance
     */
    public function addRequirements(array $requirements)
    {
        parent::addRequirements($requirements);
        $this->_addHeaderRequirements();

        return $this;
    }
    
    /**
     * Returns the requirements.
     *
     * @return array The requirements
     */
    public function getRequirements($startingWith = NULL) {
        if (NULL === $startingWith)
            return parent::getRequirements();
        
        return ('HTTP-' == $startingWith) ? $this->_headerRequirements : array();
    }
}