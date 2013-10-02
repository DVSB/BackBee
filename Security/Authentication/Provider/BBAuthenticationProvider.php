<?php

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
 * @copyright   Lp digital system
 * @author      c.rouillon
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
     * Class constructor
     * @param \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider
     * @param string $nonceDir
     * @param int $lifetime
     */
    public function __construct(UserProviderInterface $userProvider, $nonceDir, $lifetime = 300)
    {
        $this->_userProvider = $userProvider;
        $this->_nonceDir = $nonceDir;
        $this->_lifetime = $lifetime;

        if (false === file_exists($this->_nonceDir)) {
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
        if (time() - strtotime($created) > 300)
            throw new SecurityException('Request expired', SecurityException::EXPIRED_TOKEN);

        if (file_exists($this->_nonceDir . DIRECTORY_SEPARATOR . $nonce)
                && file_get_contents($this->_nonceDir . DIRECTORY_SEPARATOR . $nonce) + $this->_lifetime < time())
            throw new SecurityException('Prior authentication expired', SecurityException::EXPIRED_AUTH);

        if (md5($nonce . $created . md5($secret)) !== $digest) // To Do : $secret devrait déjà être un md5
            throw new SecurityException('Invalid authentication informations', SecurityException::INVALID_CREDENTIALS);

        file_put_contents($this->_nonceDir . DIRECTORY_SEPARATOR . $nonce, time());

        return TRUE;
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
        if (true === $this->supports($token)
                && null !== $token->getNonce()
                && true === is_writable($this->_nonceDir . DIRECTORY_SEPARATOR . $token->getNonce())) {
            @unlink($this->_nonceDir . DIRECTORY_SEPARATOR . $token->getNonce());
        }
    }

}
