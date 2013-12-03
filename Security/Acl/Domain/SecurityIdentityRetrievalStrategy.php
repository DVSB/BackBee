<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 * 
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\Security\Acl\Domain;

use Symfony\Component\Security\Core\Util\ClassUtils,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface,
    Symfony\Component\Security\Acl\Domain\SecurityIdentityRetrievalStrategy as sfStrategy;

/**
 * Strategy for retrieving security identities unshifting group identities to BackBuilder users
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Acl\Domain
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
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