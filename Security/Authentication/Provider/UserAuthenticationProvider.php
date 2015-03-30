<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use BackBee\Security\Exception\SecurityException;
use BackBee\Security\Token\UsernamePasswordToken;

/**
 * Authentication provider for username/password firewall.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class UserAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * The user provider to query.
     *
     * @var \Symfony\Component\Security\Core\User\UserProviderInterface
     */
    private $_userProvider;

    /**
     * The encoders factory.
     *
     * @var \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface
     */
    private $_encoderFactory;

    /**
     * Class constructor.
     *
     * @param \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider
     */
    public function __construct(UserProviderInterface $userProvider, EncoderFactoryInterface $encoderFactory = null)
    {
        $this->_userProvider = $userProvider;
        $this->_encoderFactory = $encoderFactory;
    }

    /**
     * Authenticate a token according to the user provider.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     *
     * @return \Symfony\Component\Security\Core\User\UserProviderInterface
     *
     * @throws SecurityException Occures on invalid connection
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return;
        }

        $username = $token->getUsername();
        if (empty($username)) {
            $username = 'NONE_PROVIDED';
        }

        $user = $this->_userProvider->loadUserByUsername($username);
        if (false === is_array($user)) {
            $user = array($user);
        }

        $authenticatedToken = false;
        while (false === $authenticatedToken) {
            if (null !== $provider = array_pop($user)) {
                $authenticatedToken = $this->_authenticateUser($token, $provider);
            } else {
                break;
            }
        }

        if (false === $authenticatedToken) {
            throw new SecurityException('Invalid authentication informations', SecurityException::INVALID_CREDENTIALS);
        }

        return $authenticatedToken;
    }

    /**
     * Checks whether this provider supports the given token.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     *
     * @return Boolean true if the implementation supports the Token, false otherwise
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof UsernamePasswordToken;
    }

    /**
     * Authenticate a token accoridng to the user provided.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param \Symfony\Component\Security\Core\User\UserInterface                  $user
     *
     * @return boolean|\BackBee\Security\Token\UsernamePasswordToken
     */
    private function _authenticateUser(TokenInterface $token, UserInterface $user)
    {
        if (null === $this->_encoderFactory) {
            return $this->_authenticateWithoutEncoder($token, $user);
        } else {
            return $this->_authenticateWithEncoder($token, $user);
        }
    }

    /**
     * Authenticate a token according to the user provided with password encoder.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param \Symfony\Component\Security\Core\User\UserInterface                  $user
     *
     * @return boolean|\BackBee\Security\Token\UsernamePasswordToken
     */
    private function _authenticateWithEncoder(TokenInterface $token, UserInterface $user)
    {
        try {
            $classname = \Symfony\Component\Security\Core\Util\ClassUtils::getRealClass($user);
            if (true === $this->_encoderFactory
                            ->getEncoder($classname)
                            ->isPasswordValid($user->getPassword(), $token->getCredentials(), $user->getSalt())) {
                return new UsernamePasswordToken($user, $user->getPassword(), $user->getRoles());
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Authenticate a token according to the user provided without any password encoders.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param \Symfony\Component\Security\Core\User\UserInterface                  $user
     *
     * @return boolean|\BackBee\Security\Token\UsernamePasswordToken
     */
    private function _authenticateWithoutEncoder(TokenInterface $token, UserInterface $user)
    {
        //@todo: don't use salt in call_user_func anymore
        if (null !== $user->getSalt() &&
                call_user_func($user->getSalt(), $token->getCredentials()) === $user->getPassword()) {
            return new UsernamePasswordToken($user, $user->getPassword(), $user->getRoles());
        } elseif ($token->getCredentials() === $user->getPassword()) {
            return new UsernamePasswordToken($user, $user->getPassword(), $user->getRoles());
        } else {
            return false;
        }
    }
}
