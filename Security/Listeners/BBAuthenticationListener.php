<?php
namespace BackBuilder\Security\Listeners;

use BackBuilder\Security\Exception\SecurityException,
    BackBuilder\Security\Token\BBUserToken;

use Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Symfony\Component\Security\Http\Firewall\ListenerInterface;

use Psr\Log\LoggerInterface;

class BBAuthenticationListener implements ListenerInterface {
    private $_context;
    private $_authenticationManager;
    private $_logger;
    
    public function __construct(SecurityContextInterface $context, AuthenticationManagerInterface $authManager, LoggerInterface $logger = null) {
        $this->_context = $context;
        $this->_authenticationManager = $authManager;
        $this->_logger = $logger;
    }
    
    public function handle(GetResponseEvent $event) {
        $request = $event->getRequest();
        
        $username = '';
        $nonce = md5(uniqid('', TRUE));
        $ecode = 0;
        $emsg = '';
        
        try {
            if (false === \BackBuilder\Services\Rpc\JsonRPCServer::isRPCInvokedMethodSecured($request)) return;
        } catch (\Exception $e) {}
        
        if ($request->headers->has('X_BB_AUTH')) {
            $pattern = '/UsernameToken Username="([^"]+)", PasswordDigest="([^"]+)", Nonce="([^"]+)", Created="([^"]+)"/';
            
            if (preg_match($pattern, $request->headers->get('X-BB-AUTH'), $matches)) {
                $username = $matches[1];
                $nonce = $matches[3];
                
                $token = new BBUserToken();
                $token->setUser($username)
                      ->setDigest($matches[2])
                      ->setNonce($nonce)
                      ->setCreated($matches[4]);
                
                try {
                    $token = $this->_authenticationManager->authenticate($token);
                    
                    if (null !== $this->_logger)
                        $this->_logger->info(sprintf('BB Authentication request succeed for user "%s"', $username));
                    
                    return $this->_context->setToken($token);
                } catch (SecurityException $e) {
                    $ecode = $e->getCode();
                    $emsg = $e->getMessage();
                    
                    if ($ecode == SecurityException::EXPIRED_AUTH)
                        $nonce = md5(uniqid('', TRUE));
                    
                    if (null !== $this->_logger)
                        $this->_logger->info(sprintf('BB Authentication request failed for user "%s": %s', $username, $e->getMessage()));
                } catch (\Exception $e) {
                    $ecode = -1;
                    $emsg = $e->getMessage();
                    
                    if (null !== $this->_logger)
                        $this->_logger->error($e->getMessage());
                }
            }

            $response = new Response();
            $response->setStatusCode(401);
            $response->headers->set('X-BB-AUTH', sprintf('UsernameToken Nonce="%s", ErrorCode="%d", ErrorMessage="%s"', $nonce, $ecode, $emsg), true);
            
            return $event->setResponse($response);
        }
        
        $response = new Response();
        $response->setStatusCode(403);
        $event->setResponse($response);
    }
}