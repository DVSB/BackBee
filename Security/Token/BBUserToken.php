<?php

namespace BackBuilder\Security\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Base class for BackBuilder token's user
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Security\Token
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class BBUserToken extends AbstractToken
{

    /**
     * Creation date of the token
     * @var \DateTime 
     */
    private $_created;

    /**
     * Digest to be checks associated to the token
     * @var string
     */
    private $_digest;

    /**
     * User's private nonce for the token
     * @var string
     */
    private $_nonce;

    /**
     * Class Constructor.
     * @param array  $roles       An array of roles
     */
    public function __construct(array $roles = array())
    {
        parent::__construct($roles);
        $this->setAuthenticated(true);
    }

    /**
     * Returns the creation date of the token
     * @codeCoverageIgnore
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->_created;
    }

    /**
     * Returns the credentials (empty for this token)
     * @codeCoverageIgnore
     * @return string
     */
    public function getCredentials()
    {
        return '';
    }

    /**
     * Returns the current digest
     * @codeCoverageIgnore
     * @return string
     */
    public function getDigest()
    {
        return $this->_digest;
    }

    /**
     * Returns the user's private nonce
     * @codeCoverageIgnore
     * @return type
     */
    public function getNonce()
    {
        return $this->_nonce;
    }

    /**
     * Sets the creation date
     * @codeCoverageIgnore
     * @param type $created
     * @return \BackBuilder\Security\Token\BBUserToken
     */
    public function setCreated($created)
    {
        $this->_created = $created;
        return $this;
    }

    /**
     * Sets the digest
     * @codeCoverageIgnore
     * @param type $digest
     * @return \BackBuilder\Security\Token\BBUserToken
     */
    public function setDigest($digest)
    {
        $this->_digest = $digest;
        return $this;
    }

    /**
     * Sets the user's private nonce
     * @codeCoverageIgnore
     * @param type $nonce
     * @return \BackBuilder\Security\Token\BBUserToken
     */
    public function setNonce($nonce)
    {
        $this->_nonce = $nonce;
        return $this;
    }

    /**
     * Sets the user
     * @codeCoverageIgnore
     * @param type $user
     * @return \BackBuilder\Security\Token\BBUserToken
     */
    public function setUser($user)
    {
        parent::setUser($user);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $serialized = unserialize(parent::serialize());
        $serialized[] = $this->_nonce;

        return serialize($serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $array = unserialize($serialized);
        $this->_nonce = array_pop($array);

        parent::unserialize(serialize($array));
    }

}