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

use BackBuilder\Security\Exception\SecurityException,
    BackBuilder\Security\Token\BBUserToken;
use Symfony\Component\Security\Core\User\UserProviderInterface,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface,
    Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;

/**
 * Retrieves BBUser for BBUserToken
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Authentication\Provider
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class BBAuthenticationProvider implements AuthenticationProviderInterface
{

    /**
     * The nonce directory
     * @var string
     */
    private $_nonceDir;

    /**
     * The user provider use to retrieve user
     * @var \Symfony\Component\Security\Core\User\UserProviderInterface
     */
    private $_userProvider;

    /**
     * The life time of the connection
     * @var int
     */
    private $_lifetime;

    /**
     * The DB Registry repository to used to store nonce rather than file
     * @var \BackBuillder\Bundle\Registry\Repository
     */
    private $_registryRepository;

    /**
     * Class constructor
     * @param \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider
     * @param string $nonceDir
     * @param int $lifetime
     * @param \BackBuillder\Bundle\Registry\Repository $registryRepository
     */
    public function __construct(UserProviderInterface $userProvider, $nonceDir, $lifetime = 300, $registryRepository = null)
    {
        $this->_userProvider = $userProvider;
        $this->_nonceDir = $nonceDir;
        $this->_lifetime = $lifetime;
        $this->_registryRepository = $registryRepository;

        if (null === $this->_registryRepository &&
                false === file_exists($this->_nonceDir)) {
            mkdir($this->_nonceDir, 0700, true);
        }
    }

    /**
     * Checks for a valid nonce file according to the WSE
     * @param string $digest The digest string send by the client
     * @param string $nonce The nonce file
     * @param string $created The creation date of the nonce
     * @param string $secret The secret (ie password) to be check
     * @return boolean
     * @throws \BackBuilder\Security\Exception\SecurityException
     */
    private function _checkNonce($digest, $nonce, $created, $secret)
    {
        if (time() - strtotime($created) > 300) {
            throw new SecurityException('Request expired', SecurityException::EXPIRED_TOKEN);
        }

        if (md5($nonce . $created . md5($secret)) !== $digest) {
            // To Do : $secret devrait déjà être un md5
            throw new SecurityException('Invalid authentication informations', SecurityException::INVALID_CREDENTIALS);
        }

        if ((null !== $value = $this->_readNonceValue($nonce)) && $value + $this->_lifetime < time()) {
//        if (file_exists($this->_nonceDir . DIRECTORY_SEPARATOR . $nonce) && file_get_contents($this->_nonceDir . DIRECTORY_SEPARATOR . $nonce) + $this->_lifetime < time())
            throw new SecurityException('Prior authentication expired', SecurityException::EXPIRED_AUTH);
        }

        $this->_writeNonceValue($nonce);
//        file_put_contents($this->_nonceDir . DIRECTORY_SEPARATOR . $nonce, time());

        return TRUE;
    }

    /**
     * Returns the nonce value if found, NULL otherwise
     * @param type $nonce
     * @return NULL|int
     */
    private function _readNonceValue($nonce)
    {
        $value = null;

        if (null === $this->_registryRepository) {
            if (true === is_readable($this->_nonceDir . DIRECTORY_SEPARATOR . $nonce)) {
                $value = file_get_contents($this->_nonceDir . DIRECTORY_SEPARATOR . $nonce);
            }
        } else {
            $value = $this->_getRegistry($nonce)
                    ->getValue();
        }

        return $value;
    }

    /**
     * Updates the nonce value
     * @param string $nonce
     */
    private function _writeNonceValue($nonce)
    {
        if (null === $this->_registryRepository) {
            file_put_contents($this->_nonceDir . DIRECTORY_SEPARATOR . $nonce, time());
        } else {
            $registry = $this->_getRegistry($nonce)
                    ->setValue(time());
            $this->_registryRepository->save($registry);
        }
    }

    /**
     * Removes the nonce
     * @param string $nonce
     */
    private function _removeNonce($nonce)
    {
        if (null === $this->_registryRepository) {
            @unlink($this->_nonceDir . DIRECTORY_SEPARATOR . $nonce);
        } else {
            $registry = $this->_getRegistry($nonce);
            $this->_registryRepository->remove($registry);
        }
    }

    /**
     * Returns a Registry entry for $nonce
     * @param string $nonce
     * @return \BackBuilder\Bundle\Registry
     */
    private function _getRegistry($nonce)
    {
        if (null === $registry = $this->_registryRepository->findOneBy(array('key' => $nonce, 'scope' => 'SECURITY.NONCE'))) {
            $registry = new \BackBuilder\Bundle\Registry();
            $registry->setKey($nonce)
                    ->setScope('SECURITY.NONCE');
        }

        return $registry;
    }

    /**
     * Attempts to authenticates a TokenInterface object.
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @return \BackBuilder\Security\Token\BBUserToken
     * @throws \BackBuilder\Security\Exception\SecurityException
     */
    public function authenticate(TokenInterface $token)
    {
        if (false === $this->supports($token)) {
            throw new SecurityException('Invalid token provided', SecurityException::UNSUPPORTED_TOKEN);
        }

        if (NULL === $user = $this->_userProvider->loadUserByUsername($token->getUsername())) {
            throw new SecurityException('Unknown user', SecurityException::UNKNOWN_USER);
        }

        try {
            $this->_checkNonce($token->getDigest(), $token->getNonce(), $token->getCreated(), $user->getPassword());
        } catch (SecurityException $e) {
            $this->clearNonce($token);
            throw $e;
        }

        $validToken = new BBUserToken($user->getRoles());
        $validToken->setUser($user)
                ->setNonce($token->getNonce());

        return $validToken;
    }

    /**
     * Checks whether this provider supports the given token.
     * @codeCoverageIgnore
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @return boolean
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof BBUserToken;
    }

    /**
     * Clear nonce file for the current token
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     */
    public function clearNonce(TokenInterface $token)
    {
        if (true === $this->supports($token) && null !== $token->getNonce()) {
            $this->_removeNonce($token->getNonce());
        }
    }

}
