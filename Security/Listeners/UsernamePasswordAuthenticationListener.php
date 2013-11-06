<?php
namespace BackBuilder\Security\Listeners;

use BackBuilder\Security\Token\UsernamePasswordToken;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Symfony\Component\Security\Http\Firewall\ListenerInterface,
    Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;

use Psr\Log\LoggerInterface;

class UsernamePasswordAuthenticationListener implements ListenerInterface {
    private $_context;
    private $_authenticationManager;
    private $_login_path;
    private $_check_path;
    private $_logger;
    
    public function __construct(SecurityContextInterface $context, AuthenticationManagerInterface $authManager, $login_path = NULL, $check_path = NULL, LoggerInterface $logger = null) {
        $this->_context = $context;
        $this->_authenticationManager = $authManager;
        $this->_login_path = $login_path;
        $this->_check_path = $check_path;
        $this->_logger = $logger;
    }
    
    public function handle(GetResponseEvent $event) {
        $request = $event->getRequest();
        
        $token = $this->_context->getToken();
        $errmsg = '';
        
        if (null !== $request->request->get('login') && null !== $request->request->get('password')) {
            $token = new UsernamePasswordToken($request->request->get('login'), $request->request->get('password'));
            $token->setUser($request->request->get('login'), $request->request->get('password'));
            try {
                $token = $this->_authenticationManager->authenticate($token);

                if (null !== $this->_logger)
                    $this->_logger->info(sprintf('Authentication request succeed for user "%s"', $token->getUsername()));

            } catch (\Exception $e) {
                $errmsg = $e->getMessage();
                if (null !== $this->_logger)
                    $this->_logger->info(sprintf('Authentication request failed for user "%s": %s', $token->getUsername(), $e->getMessage()));
            }
        }

        if (is_a($token, 'BackBuilder\Security\Token\UsernamePasswordToken') && $errmsg != '') {
            if (null !== $this->_login_path) {
                $redirect = $request->query->get('redirect');
                if (null === $redirect)
                    $redirect = $request->request->get('redirect', '');
                if ('' === $redirect) {
                    $redirect = $request->getPathInfo();
                }
                if (NULL !== $qs = $request->getQueryString())
                    $redirect .= '?'.$qs;

                $response = new RedirectResponse($event->getRequest()->getUriForPath($this->_login_path.'?redirect='.urlencode($redirect).'&errmsg='.urlencode($errmsg).'&login='.urlencode($request->request->get('login'))));
                $event->setResponse($response);
                return;
            }

            $response = new Response();
            $response->setStatusCode(403);
            $event->setResponse($response);
        }
        
        if (null !== $token && is_a($token, 'BackBuilder\Security\Token\UsernamePasswordToken')) {
            $this->_context->setToken($token);
            
            if ($request->request->get('redirect')) {
                $response = new RedirectResponse($request->getBaseUrl().$request->request->get('redirect'));
                $event->setResponse($response);
            }
        }
    }
}