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
    BackBuilder\Security\Token\PublicKeyToken;
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
 * @author      k.golovin
 */
class PublicKeyAuthenticationListener implements ListenerInterface
{
    const AUTH_PUBLIC_KEY_TOKEN = 'X-API-KEY';
    const AUTH_SIGNATURE_TOKEN = 'X-API-SIGNATURE';


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

        $publicKey = $request->headers->get(self::AUTH_PUBLIC_KEY_TOKEN);

        $token = new PublicKeyToken();
        $token->setUser($publicKey);

        $token->publicKey = $publicKey;
        $token->request = $request;

        $token->signature = $request->headers->get(self::AUTH_SIGNATURE_TOKEN);

        try {
            $token = $this->_authenticationManager->authenticate($token);

            if (null !== $this->_logger) {
                $this->_logger->info(sprintf('PubliKey Authentication request succeed for public key "%s"', $token->getUsername()));
            }

            return $this->_context->setToken($token);
        } catch (SecurityException $e) {
            if (null !== $this->_logger) {
                $this->_logger->info(sprintf('PubliKey Authentication request failed for public key "%s": %s', $token->getUsername(), str_replace("\n", ' ', $e->getMessage())));
            }
            throw $e;
        } catch (\Exception $e) {
            if (null !== $this->_logger) {
                $this->_logger->error($e->getMessage());
            }

            throw $e;
        }
    }

}