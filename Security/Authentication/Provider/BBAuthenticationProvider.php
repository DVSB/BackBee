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

use BackBuilder\Bundle\Registry;
use BackBuilder\Security\Encoder\RequestSignatureEncoder;
use BackBuilder\Security\Exception\SecurityException;
use BackBuilder\Security\Token\BBUserToken;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Security\Core\User\UserProviderInterface;

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
    private $nonce_directory;

    /**
     * The user provider use to retrieve user
     * @var \Symfony\Component\Security\Core\User\UserProviderInterface
     */
    protected $user_provider;

    /**
     * The life time of the connection
     * @var int
     */
    protected $lifetime;

    /**
     * The DB Registry repository to used to store nonce rather than file
     * @var \BackBuillder\Bundle\Registry\Repository
     */
    private $registry_repository;


    /**
     * The encoders factory
     * @var \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface
     */
    protected $encoder_factory;

    /**
     * Class constructor
     * @param \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider
     * @param string $nonceDir
     * @param int $lifetime
     * @param \BackBuillder\Bundle\Registry\Repository $registryRepository
     */
    public function __construct(UserProviderInterface $userProvider, $nonceDir, $lifetime = 300, $registryRepository = null, EncoderFactoryInterface $encoderFactory = null)
    {
        $this->user_provider = $userProvider;
        $this->nonce_directory = $nonceDir;
        $this->lifetime = $lifetime;
        $this->registry_repository = $registryRepository;
        $this->encoder_factory = $encoderFactory;

        if (null === $this->registry_repository && false === file_exists($this->nonce_directory)) {
            mkdir($this->nonce_directory, 0700, true);
        }
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

        if (null === $user = $this->user_provider->loadUserByUsername($token->getUsername())) {
            throw new SecurityException('Unknown user', SecurityException::UNKNOWN_USER);
        }

        try {
            $secret = $user->getPassword();
            if ($this->encoder_factory) {
                try {
                    $encoder = $this->encoder_factory->getEncoder($user);

                    if ($encoder instanceof PlaintextPasswordEncoder) {
                        $secret = md5($secret);
                    } elseif ($encoder instanceof MessageDigestPasswordEncoder) {
                        // $secret is already md5 encoded
                        // NB: only md5 algo without salt is currently supported due to frontend dependency
                    } else {
                        // currently there is a dependency on md5 in frontend so all other encoders can't be supported
                        throw new \RuntimeException('Encoder is not supported: ' . get_class($encoder));
                    }
                } catch(\RuntimeException $e) {
                    // no encoder defined
                    $secret = md5($secret);
                }
            } else {
                // no encoder - still have to encode with md5
                $secret = md5($secret);
            }

            $this->checkNonce($token, $secret);
        } catch (SecurityException $e) {
            $this->clearNonce($token);
            throw $e;
        }

        $validToken = new BBUserToken($user->getRoles());
        $validToken->setUser($user)->setNonce($token->getNonce());

        $this->writeNonceValue($validToken);

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
            $this->removeNonce($token->getNonce());
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
    protected function checkNonce(BBUserToken $token, $secret)
    {
        $digest = $token->getDigest();
        $nonce = $token->getNonce();
        $created = $token->getCreated();

        if (time() - strtotime($created) > 300) {
            throw new SecurityException('Request expired', SecurityException::EXPIRED_TOKEN);
        }

        if (md5($nonce . $created . $secret) !== $digest) {
            throw new SecurityException('Invalid authentication informations', SecurityException::INVALID_CREDENTIALS);
        }

        $value = $this->readNonceValue($nonce);
        if (null !== $value && $value[0] + $this->lifetime < time()) {
//        if (file_exists($this->nonce_directory . DIRECTORY_SEPARATOR . $nonce) && file_get_contents($this->nonce_directory . DIRECTORY_SEPARATOR . $nonce) + $this->lifetime < time())
            throw new SecurityException('Prior authentication expired', SecurityException::EXPIRED_AUTH);
        }
//        file_put_contents($this->nonce_directory . DIRECTORY_SEPARATOR . $nonce, time());

        return true;
    }

    /**
     * Returns the nonce value if found, NULL otherwise
     * @param type $nonce
     * @return NULL|int
     */
    protected function readNonceValue($nonce)
    {
        $value = null;

        if (null === $this->registry_repository) {
            if (true === is_readable($this->nonce_directory . DIRECTORY_SEPARATOR . $nonce)) {
                $value = file_get_contents($this->nonce_directory . DIRECTORY_SEPARATOR . $nonce);
            }
        } else {
            $value = $this->getRegistry($nonce)->getValue();
        }

        if (null !== $value) {
            $value = explode(';', $value);
        }

        return $value;
    }

    /**
     * Updates the nonce value
     * @param string $nonce
     */
    protected function writeNonceValue(BBUserToken $token)
    {
        $now = time();
        $nonce = $token->getNonce();
        $signature_generator = new RequestSignatureEncoder();
        $signature = $signature_generator->createSignature($token);
        if (null === $this->registry_repository) {
            file_put_contents($this->nonce_directory . DIRECTORY_SEPARATOR . $nonce, "$now;$signature");
        } else {
            $registry = $this->getRegistry($nonce)->setValue("$now;$signature");
            $this->registry_repository->save($registry);
        }
    }

    /**
     * Removes the nonce
     * @param string $nonce
     */
    protected function removeNonce($nonce)
    {
        if (null === $this->registry_repository) {
            @unlink($this->nonce_directory . DIRECTORY_SEPARATOR . $nonce);
        } else {
            $registry = $this->getRegistry($nonce);
            $this->registry_repository->remove($registry);
        }
    }

    /**
     * Returns a Registry entry for $nonce
     * @param string $nonce
     * @return \BackBuilder\Bundle\Registry
     */
    private function getRegistry($nonce)
    {
        if (null === $registry = $this->registry_repository->findOneBy(array('key' => $nonce, 'scope' => 'SECURITY.NONCE'))) {
            $registry = new Registry();
            $registry->setKey($nonce)->setScope('SECURITY.NONCE');
        }

        return $registry;
    }
}
