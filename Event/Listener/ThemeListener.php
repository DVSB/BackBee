<?php
namespace BackBuilder\Event\Listener;

use Symfony\Component\Security\Core\Event\AuthenticationEvent;

use BackBuilder\Security\Token\BBUserToken;

class ThemeListener
{
    public static function onUserIsAuthenticated(AuthenticationEvent $event)
    {
        $application = $event->getDispatcher()->getApplication();
        $application->debug('User not Authenticated');
        
        if ($event->getAuthenticationToken() instanceof BBUserToken) {
           $application->getTheme()->init();
        } else {
           $application->getTheme()->init();
        }
    }
    
    public static function onUserIsNotAuthenticated(AuthenticationEvent $event)
    {
        $event->getDispatcher()->getApplication()->debug('User not Authenticated');
        $event->getDispatcher()->getApplication()->getTheme()->init();
    }
}