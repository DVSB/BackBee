<?php
namespace BackBuilder\Security\Authorization\Adaptator;

use BackBuilder\BBApplication,
    BackBuilder\Security\Role\Role;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Yml implements IRoleReaderAdaptator
{
    private $_roles;
    
    /**
     * {@inheritdoc}
     */
    public function __construct(BBApplication $application, $section = 'roles')
    {
        $this->_roles = $application->getConfig()->getSecurityConfig($section);
    }
    
    /**
     * {@inheritdoc}
     */
    public function extractRoles(TokenInterface $token)
    {
        $user_roles = array();
        foreach ($this->_roles as $role => $users) {
            if (is_array($users) && in_array($token->getUsername(), $users)) {
                $user_roles[] = new Role($role);
            }
        }
        
        return $user_roles;
    }
}