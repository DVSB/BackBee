<?php
namespace BackBuilder\Routing\Matcher;

use BackBuilder\Util\File;

use Symfony\Component\Routing\Route,
    Symfony\Component\Routing\Matcher\UrlMatcher as sfUrlMatcher;

class UrlMatcher extends sfUrlMatcher {
    /**
     * Handles specific route requirements.
     *
     * @param string $pathinfo The path
     * @param string $name     The route name
     * @param string $route    The route
     *
     * @return array The first element represents the status, the second contains additional information
     */
    protected function handleRouteRequirements($pathinfo, $name, Route $route) {
        $pathinfo = File::normalizePath($pathinfo, '/', false);
        $status = parent::handleRouteRequirements($pathinfo, $name, $route);
        
        if (self::REQUIREMENT_MATCH == $status[0] && 0 < count($route->getRequirements('HTTP-'))) {
            if (NULL === $request = $this->getContext()->getRequest())
                return array(self::REQUIREMENT_MISMATCH, NULL);
            
	        $requestMatcher = new RequestMatcher(null, null, null, null, array(), $route->getRequirements('HTTP-'));
	        $status = array( $requestMatcher->matches($request) ? self::REQUIREMENT_MATCH : self::REQUIREMENT_MISMATCH, NULL);
        }
        
        return $status;
    }
    
    /**
     * Tries to match a URL with a set of routes.
     *
     * @param  string $pathinfo The path info to be parsed (raw format, i.e. not urldecoded)
     *
     * @return array An array of parameters
     *
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    public function match($pathinfo) {
        $pathinfo = File::normalizePath($pathinfo, '/', false);
        return parent::match($pathinfo);
    }
}