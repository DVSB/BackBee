<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 * 
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

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
 * @subpackage  Logout
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
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
     * @param \BackBuilder\Security\Authentication\Provider\BBAuthenticationProvider $authentication_provider
     */
    public function __construct(BBAuthenticationProvider $authentication_provider)
    {
        $this->_authentication_provider = $authentication_provider;
    }

    /**
     * Invalidate the current BB connection
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $this->_authentication_provider->clearNonce($token);
    }

}