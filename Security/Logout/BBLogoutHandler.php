<?php

namespace BackBuilder\Security\Logout;

use BackBuilder\Security\Authentication\Provider\BBAuthenticationProvider;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Security\Http\Logout\LogoutHandlerInterface,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Handler for clearing nonce file of BB connection
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class BBLogoutHandler implements LogoutHandlerInterface
{

    /**
     * The BB user authentication provider
     * @var \$authentication_provider
     */
    private $_authentication_provider;

    /**
     * Class constructor
     * @codeCoverageIgnore
     * @param \BackBuilder\Security\Authentication\Provider\BBAuthenticationProvider $authentication_provider
     */
    public function __construct(BBAuthenticationProvider $authentication_provider)
    {
        $this->_authentication_provider = $authentication_provider;
    }

    /**
     * Invalidate the current BB connection
     * @codeCoverageIgnore
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $this->_authentication_provider->clearNonce($token);
    }

}