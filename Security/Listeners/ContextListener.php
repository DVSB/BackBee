<?php
namespace BackBuilder\Security\Listeners;

use Symfony\Component\HttpFoundation\Session\Session,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\Security\Http\Firewall\ContextListener as sfContextListener;

class ContextListener extends sfContextListener {
    /**
     * Initiate session if not available then reads the SecurityContext from it.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     */
    public function handle(GetResponseEvent $event) {
        $request = $event->getRequest();
        $application = $event->getKernel()->getApplication();
        if (NULL !== $application && !$request->hasSession()) {
            if (!$application->getContainer()->has('bb_session')) {
                $application->getContainer()->set('bb_session', new Session());
            }
            $application->getContainer()->get('bb_session')->start();
            $application->info("Session started");
            
            $event->getRequest()->setSession($application->getContainer()->get('bb_session'));
        }
        
        parent::handle($event);
    }
}