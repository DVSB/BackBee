<?php
namespace BackBuilder\Routing\Matcher;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RequestMatcher as sfRequestMatcher;

class RequestMatcher extends sfRequestMatcher {
    /**
     * Headers attributes.
     *
     * @var array
     */
    private $_headers;

    public function __construct($path = null, $host = null, $methods = null, $ip = null, array $attributes = array(), array $headers = array()) {
        parent::__construct($path, $host, $methods, $ip, $attributes);
        $this->_headers = $headers;
    }
    
    /**
     * Adds a check for header attribute.
     *
     * @param string $key    The header attribute name
     * @param string $regexp A Regexp
     */
    public function matchHeader($key, $regexp) {
        $this->_headers[$key] = $regexp;
    }
    
    /**
     * Adds checks for header attributes.
     *
     * @param array    the header attributes to check array(attribute1 => regexp1, ettribute2 => regexp2, ...)
     */
    public function matchHeaders($attributes) {
        $attributes = (array) $attributes;
        foreach($attributes as $key => $regexp)
            $this->matchHeader($key, $regexp);
    }
    
    public function matches(Request $request) {
        foreach ($this->_headers as $key => $pattern) {
            if (!preg_match('#'.str_replace('#', '\\#', $pattern).'#', $request->headers->get($key))) {
                return false;
            }
        }
        
        return parent::matches($request);
    }
}