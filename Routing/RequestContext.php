<?php
namespace BackBuilder\Routing;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Routing\RequestContext as sfRequestContext;

class RequestContext extends sfRequestContext {
    private $_request;
    
    /**
     * @codeCoverageIgnore
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function fromRequest(Request $request) {
        $this->_request = $request;
        parent::fromRequest($request);
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getRequest() {
        return $this->_request;
    }
}