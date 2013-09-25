<?php
namespace BackBuilder\Renderer\Helper;

class isGranted extends AHelper
{
    public function __invoke($attributes = null, $bypassBBUser = true)
    {
        $application = $this->_renderer->getApplication();
        
        if (true === $bypassBBUser && null !== $application->getBBUserToken()) return true;
        if (null === $token = $application->getSecurityContext()->getToken()) return false;
        if (null === $attributes) return true;
        
        $attributes = (array) $attributes;
        
        return $application->getSecurityContext()->isGranted($attributes, $this->_renderer->getObject());
    }
}