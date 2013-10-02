<?php
namespace BackBuilder\Security;

use Symfony\Component\Security\Http\FirewallMap as sfFirewallMap;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;

class FirewallMap extends sfFirewallMap {
    protected $map;
    
    public function unshift(RequestMatcherInterface $requestMatcher = null, array $listeners = array(), ExceptionListener $exceptionListener = null) {
        array_unshift($this->map, array($requestMatcher, $listeners, $exceptionListener));
    }
}