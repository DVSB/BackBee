<?php

namespace BackBuilder\Security\Listeners;

use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

/**
 * LogoutListener logout users.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class LogoutListener implements ListenerInterface
{

    private $securityContext;
    private $handlers;
    private $httpUtils;
    private $successHandler;

    /**
     * Constructor
     *
     * @param SecurityContextInterface      $securityContext
     * @param HttpUtils                     $httpUtils       An HttpUtilsInterface instance
     * @param LogoutSuccessHandlerInterface $successHandler  A LogoutSuccessHandlerInterface instance
     * @param array                         $options         An array of options to process a logout attempt
     * @param CsrfProviderInterface         $csrfProvider    A CsrfProviderInterface instance
     */
    public function __construct(SecurityContextInterface $securityContext, HttpUtils $httpUtils, LogoutSuccessHandlerInterface $successHandler)
    {
        $this->securityContext = $securityContext;
        $this->httpUtils = $httpUtils;
        $this->handlers = array();
        $this->successHandler = $successHandler;
    }

    /**
     * Adds a logout handler
     *
     * @codeCoverageIgnore
     * @param LogoutHandlerInterface $handler
     */
    public function addHandler(LogoutHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * Performs the logout if requested
     *
     * If a CsrfProviderInterface instance is available, it will be used to
     * validate the request.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     * @throws InvalidCsrfTokenException if the CSRF token is invalid
     * @throws RuntimeException if the LogoutSuccessHandlerInterface instance does not return a response
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $response = $this->successHandler->onLogoutSuccess($request);
        if (!$response instanceof Response) {
            throw new \RuntimeException('Logout Success Handler did not return a Response.');
        }

        // handle multiple logout attempts gracefully
        if ($token = $this->securityContext->getToken()) {
            foreach ($this->handlers as $handler) {
                $handler->logout($request, $response, $token);
            }
        }

        $this->securityContext->setToken(null);
        $event->setResponse($response);
    }

    /**
     * Whether this request is asking for logout.
     *
     * The default implementation only processed requests to a specific path,
     * but a subclass could change this to logout requests where
     * certain parameters is present.
     *
     * @param Request $request
     *
     * @return Boolean
     */
    protected function requiresLogout(Request $request)
    {
        //return $this->httpUtils->checkRequestPath($request, $this->options['logout_path']);
    }

}
