<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Security\Authorization\Adaptator;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use BackBee\BBApplication;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Group implements RoleReaderAdaptatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(BBApplication $application)
    {
    }

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
