<?php

namespace BackBuilder\Security\Acl\Domain;

use Symfony\Component\Security\Core\Util\ClassUtils,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface,
    Symfony\Component\Security\Acl\Domain\SecurityIdentityRetrievalStrategy as sfStrategy;

/**
 * Strategy for retrieving security identities unshifting group identities to BackBuilder users
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security\Acl\Domain
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class SecurityIdentityRetrievalStrategy extends sfStrategy
{

    /**
     * Retrieves the available security identities for the given token
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @return array An array of SecurityIdentityInterface implementations
     */
    public function getSecurityIdentities(TokenInterface $token)
    {
        $sids = parent::getSecurityIdentities($token);

        if ($token->getUser() instanceof \BackBuilder\Security\User) {
            foreach ($token->getUser()->getGroups() as $group) {
                $securityIdentity = new UserSecurityIdentity($group->getIdentifier(), ClassUtils::getRealClass($group));
                array_unshift($sids, $securityIdentity);
            }
        }

        return $sids;
    }

}