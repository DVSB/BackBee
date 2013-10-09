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

    public function getCreated() {
        return $this->_created;
    }
    
    public function getCredentials() {
        return '';
    }
    
    public function getDigest() {
        return $this->_digest;
    }
    
    public function getNonce() {
        return $this->_nonce;
    }
    
    public function setCreated($created) {
        $this->_created = $created;
        return $this;
    }
    
    public function setDigest($digest) {
        $this->_digest = $digest;
        return $this;
    }
    
    public function setNonce($nonce) {
        $this->_nonce = $nonce;
        return $this;
    }
    
    public function setUser($user) {
        parent::setUser($user);
        return $this;
    }
}