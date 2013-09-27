<?php

namespace BackBuilder\Security\Logout;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Logout success handler for BB connection
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class BBLogoutSuccessHandler implements LogoutSuccessHandlerInterface
{

    /**
     * Resend the current Uri
     * @codeCoverageIgnore
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function onLogoutSuccess(Request $request)
    {
        return new RedirectResponse($request->getUri());
    }

}