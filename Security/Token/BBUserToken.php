<?php
namespace BackBuilder\Security\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class BBUserToken extends AbstractToken {
    private $_created;
    private $_digest;
    private $_nonce;
    
    /**
     * Constructor.
     * @param array  $roles       An array of roles
     */
    public function __construct(array $roles = array()) {
        parent::__construct($roles);
//        parent::setAuthenticated(count($roles) > 0);
        $this->setAuthenticated(true);
    }

    /**
     * @codeCoverageIgnore
     * @return \DateTime
     */
    public function getCreated() {
        return $this->_created;
    }
    
    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getCredentials() {
        return '';
    }
    
    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getDigest() {
        return $this->_digest;
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getNonce() {
        return $this->_nonce;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $created
     * @return \BackBuilder\Security\Token\BBUserToken
     */
    public function setCreated($created) {
        $this->_created = $created;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $digest
     * @return \BackBuilder\Security\Token\BBUserToken
     */
    public function setDigest($digest) {
        $this->_digest = $digest;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $nonce
     * @return \BackBuilder\Security\Token\BBUserToken
     */
    public function setNonce($nonce) {
        $this->_nonce = $nonce;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $user
     * @return \BackBuilder\Security\Token\BBUserToken
     */
    public function setUser($user) {
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