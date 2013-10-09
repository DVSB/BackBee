<?php
namespace BackBuilder\Security\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class UsernamePasswordToken extends AbstractToken {
    private $_credentials;
    
    /**
     * Constructor.
     * @param array  $roles       An array of roles
     */
    public function __construct($user, $credentials, array $roles = array()) {
        parent::__construct($roles);
        
        $this->setUser($user);
        $this->_credentials = $credentials;
//        $this->roles = $roles;
        
        parent::setAuthenticated(count($roles) > 0);
    }
    
    public function getCredentials() {
        return $this->_credentials;
    }
    
    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        parent::eraseCredentials();

        $this->_credentials = null;
    }
}