<?php
namespace BackBuilder\Security\Token;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken as sfAnonymousToken;

class AnonymousToken extends sfAnonymousToken {
    /**
     * Constructor.
     * @codeCoverageIgnore
     * @param string          $key   The key shared with the authentication provider
     * @param string          $user  The user
     * @param RoleInterface[] $roles An array of roles
     */
    public function __construct($key, $user, array $roles = array())
    {
        parent::__construct($key, $user, $roles);

        $this->setAuthenticated(true);
    }    
}