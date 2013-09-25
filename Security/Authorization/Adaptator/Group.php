<?php
namespace BackBuilder\Security\Authorization\Adaptator;

use BackBuilder\BBApplication;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Group implements IRoleReaderAdaptator
{
 
    /**
     * {@inheritdoc}
     */
    public function __construct(BBApplication $application) {}
    
    /**
     * {@inheritdoc}
     */
    public function extractRoles(TokenInterface $token)
    {
        $user_roles = array();
        foreach ($token->getUser()->getGroups() as $group) {
           $tmp = array();
           foreach ($group->getRoles() as $role) {
               $tmp[$role->getRole()] = $role;
           }
           $user_roles = array_merge($tmp, $user_roles);
        }
        
        return $user_roles;
    }
}