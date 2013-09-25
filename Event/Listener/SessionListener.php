<?php
namespace BackBuilder\Event\Listener;

use Symfony\Component\HttpFoundation\Session\Session,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Listener to FrontController events :
 *    - frontcontroller.request: occurs while a new request is received
 *    - frontcontroller.response: occurs before a response is send
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event\Listener
 * @copyright   Lp system
 * @author      c.rouillon
 */
class SessionListener {
    public function handle(GetResponseEvent $event) {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }
        
        $request = $event->getRequest();
        if ($request->hasSession()) {
            return;
        }
        
        if (NULL !== $application = $event->getKernel()->getApplication()) {
            if (!$application->getContainer()->has('session'))
                $application->getContainer()->set('session', new Session());
                
            $application->getContainer()->get('session')->start();
            $application->debug("Session started");
            
            $request->setSession($application->getContainer()->get('session'));
        }
    }
}