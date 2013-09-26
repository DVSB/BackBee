<?php
namespace BackBuilder\Security\Repository;

use Symfony\Component\Security\Core\User\UserCheckerInterface,
    Symfony\Component\Security\Core\User\UserInterface,
    Symfony\Component\Security\Core\User\UserProviderInterface,
    Symfony\Component\Security\Core\Exception\UnsupportedUserException;;

use Doctrine\ORM\EntityRepository;

/**
 */
class UserRepository extends EntityRepository implements UserProviderInterface, UserCheckerInterface {
    /**
     * @codeCoverageIgnore
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     */
    public function checkPreAuth(UserInterface $user) { }
    
    /**
     * @codeCoverageIgnore
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     */
    public function checkPostAuth(UserInterface $user) { }
    
    /**
     * @codeCoverageIgnore
     * @param type $username
     * @return type
     */
    public function loadUserByUsername($username) {
        return $this->findOneBy(array('_login' => $username));
    }
    
    public function refreshUser(UserInterface $user) {
        if (false === $this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Unsupported User class `%s`.', get_class($user)));
        }
        
        return $user;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $class
     * @return type
     */
    public function supportsClass($class) {
        return ($class == 'BackBuilder\Security\User');
    }
}