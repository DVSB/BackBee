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

namespace BackBuilder\Security\Authentication\Provider;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use BackBuilder\Security\Token\PublicKeyToken,
    BackBuilder\Security\Exception\SecurityException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface,
    Symfony\Component\Security\Core\User\UserInterface,
    Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface,
    Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;

/**
 * Authentication provider for username/password firewall
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Authentication\Provider
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PublicKeyAuthenticationProvider implements AuthenticationProviderInterface
{

    /**
     * The user provider to query
     * @var \Symfony\Component\Security\Core\User\UserProviderInterface
     */
    private $_userProvider;

    /**
     * The encoders factory
     * @var \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface
     */
    private $_encoderFactory;

    /**
     * Class constructor
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
     * @return \Symfony\Component\Security\Core\User\UserProviderInterface
     * @throws SecurityException Occures on invalid connection
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        $publicKey = $token->getUsername();
        
        $user = $this->_userProvider->loadUserByPublicKey($publicKey);

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
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @return Boolean true if the implementation supports the Token, false otherwise
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof PublicKeyToken;
    }

    /**
     * Authenticate a token accoridng to the user provided
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     * @return boolean|\BackBuilder\Security\Token\UsernamePasswordToken
     */
    private function _authenticateUser(TokenInterface $token, UserInterface $user)
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

}
