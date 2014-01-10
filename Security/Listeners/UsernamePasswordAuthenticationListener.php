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

namespace BackBuilder\Security\Listeners;

use BackBuilder\Security\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Symfony\Component\Security\Http\Firewall\ListenerInterface,
    Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Listeners
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class UsernamePasswordAuthenticationListener implements ListenerInterface
{

    private $_context;
    private $_authenticationManager;
    private $_login_path;
    private $_check_path;
    private $_logger;

    public function __construct(SecurityContextInterface $context, AuthenticationManagerInterface $authManager, $login_path = NULL, $check_path = NULL, LoggerInterface $logger = null)
    {
        $this->_context = $context;
        $this->_authenticationManager = $authManager;
        $this->_login_path = $login_path;
        $this->_check_path = $check_path;
        $this->_logger = $logger;
    }

    public function handle(GetResponseEvent $event)
    {
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
                    $redirect .= '?' . $qs;

                $response = new RedirectResponse($event->getRequest()->getUriForPath($this->_login_path . '?redirect=' . urlencode($redirect) . '&errmsg=' . urlencode($errmsg) . '&login=' . urlencode($request->request->get('login'))));
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
                $response = new RedirectResponse($request->getBaseUrl() . $request->request->get('redirect'));
                $event->setResponse($response);
            }
        }
    }

}