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

namespace BackBuilder\Security\Authentication;

use Symfony\Component\EventDispatcher\EventDispatcherInterface,
    Symfony\Component\Security\Core\AuthenticationEvents,
    Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface,
    Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface,
    Symfony\Component\Security\Core\Event\AuthenticationEvent,
    Symfony\Component\Security\Core\Event\AuthenticationFailureEvent,
    Symfony\Component\Security\Core\Exception\AccountStatusException,
    Symfony\Component\Security\Core\Exception\AuthenticationException,
    Symfony\Component\Security\Core\Exception\ProviderNotFoundException;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Authentication
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class AuthenticationManager implements AuthenticationManagerInterface
{

    private $_providers;
    private $_eraseCredentials;
    private $_eventDispatcher;

    /**
     * Constructor.
     *
     * @param AuthenticationProviderInterface[] $providers        An array of AuthenticationProviderInterface instances
     * @param Boolean                           $eraseCredentials Whether to erase credentials after authentication or not
     */
    public function __construct(array $providers, $dispatcher = null, $eraseCredentials = true)
    {
        $this->addProviders($providers);

        if (null !== $dispatcher)
            $this->setEventDispatcher($dispatcher);

        $this->_eraseCredentials = (Boolean) $eraseCredentials;
    }

    public function setEventDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->_eventDispatcher = $dispatcher;
        return $this;
    }

    public function addProvider(AuthenticationProviderInterface $provider)
    {
        $this->_providers[] = $provider;
        return $this;
    }

    public function addProviders(array $providers)
    {
        foreach ($providers as $provider)
            $this->addProvider($provider);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        $lastException = null;
        $result = null;

        foreach ($this->_providers as $provider) {
            if (!$provider->supports($token)) {
                continue;
            }

            try {
                $result = $provider->authenticate($token);

                if (null !== $result) {
                    break;
                }
            } catch (AccountStatusException $e) {
                throw $e;
            } catch (AuthenticationException $e) {
                $lastException = $e;
            }
        }

        if (null !== $result) {
            if (true === $this->_eraseCredentials) {
                $result->eraseCredentials();
            }

            if (null !== $this->_eventDispatcher) {
                $this->_eventDispatcher->dispatch(AuthenticationEvents::AUTHENTICATION_SUCCESS, new AuthenticationEvent($result));
            }

            return $result;
        }

        if (null === $lastException) {
            $lastException = new ProviderNotFoundException(sprintf('No Authentication Provider found for token of class "%s".', get_class($token)));
        }

        if (null !== $this->_eventDispatcher) {
            $this->_eventDispatcher->dispatch(AuthenticationEvents::AUTHENTICATION_FAILURE, new AuthenticationFailureEvent($token, $lastException));
        }

        throw $lastException;
    }

}