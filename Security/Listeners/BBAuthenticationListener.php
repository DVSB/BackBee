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

use BackBuilder\Security\Exception\SecurityException,
    BackBuilder\Security\Token\BBUserToken;
use Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Psr\Log\LoggerInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Listeners
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class BBAuthenticationListener implements ListenerInterface
{

    private $_context;
    private $_authenticationManager;
    private $_logger;

    public function __construct(SecurityContextInterface $context, AuthenticationManagerInterface $authManager, LoggerInterface $logger = null)
    {
        $this->_context = $context;
        $this->_authenticationManager = $authManager;
        $this->_logger = $logger;
    }

    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $username = '';
        $nonce = md5(uniqid('', true));
        $ecode = 0;
        $emsg = '';

        try {
            if (false === \BackBuilder\Services\Rpc\JsonRPCServer::isRPCInvokedMethodSecured($request))
                return;
        } catch (\Exception $e) {

        }

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

                    if ($ecode == SecurityException::EXPIRED_AUTH) {
                        $nonce = md5(uniqid('', true));
                    }

                    if (null !== $this->_logger) {
                        $this->_logger->info(sprintf('BB Authentication request failed for user "%s": %s', $username, $e->getMessage()));
                    }
                } catch (\Exception $e) {
                    $ecode = -1;
                    $emsg = $e->getMessage();

                    if (null !== $this->_logger)
                        $this->_logger->error($e->getMessage());
                }
            }

            $response = new Response();
            $response->setStatusCode(401);

            // remove new line characters as php will drop the header if the exception message contains a new line character
            $emsgSanitized = str_replace(array("\n"), " ", $emsg);
            $response->headers->set('X-BB-AUTH', sprintf('UsernameToken Nonce="%s", ErrorCode="%d", ErrorMessage="%s"', $nonce, $ecode, $emsgSanitized), true);

            return $event->setResponse($response);
        }

        $response = new Response();
        $response->setStatusCode(403);
        $event->setResponse($response);
    }

}