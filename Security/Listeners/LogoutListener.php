<?php

namespace BackBuilder\Security\Listeners;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Symfony\Component\Security\Http\HttpUtils,
    Symfony\Component\Security\Http\Logout\LogoutHandlerInterface,
    Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface,
    Symfony\Component\Security\Http\Firewall\ListenerInterface;

/**
 * LogoutListener logout users
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Security\Listeners
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class LogoutListener implements ListenerInterface
{

    /**
     * The current security context
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    private $securityContext;

    /**
     * An array of logout handlers
     * @var array 
     */
    private $handlers;

    /**
     * @var \Symfony\Component\Security\Http\HttpUtils 
     */
    private $httpUtils;

    /**
     * On success handler
     * @var \Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface 
     */
    private $successHandler;

    /**
     * Class constructor
     * @param \Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     * @param \Symfony\Component\Security\Http\HttpUtils $httpUtils An HttpUtilsInterface instance
     * @param \Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface $successHandler A LogoutSuccessHandlerInterface instance
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
     * @param \Symfony\Component\Security\Http\Logout\LogoutHandlerInterface $handler
     * @codeCoverageIgnore
     */
    public function addHandler(LogoutHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * Performs the logout if requested
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event A GetResponseEvent instance
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
        if (null !== $token = $this->securityContext->getToken()) {
            foreach ($this->handlers as $handler) {
                $handler->logout($request, $response, $token);
            }
        }

        $this->securityContext->setToken(null);
        $event->setResponse($response);
    }

    /**
     * Whether this request is asking for logout.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return Boolean
     * @codeCoverageIgnore
     */
    protected function requiresLogout(Request $request)
    {
        return true;
    }

}
