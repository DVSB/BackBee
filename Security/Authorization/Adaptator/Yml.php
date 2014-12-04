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

namespace BackBuilder\Security\Authorization\Adaptator;

use BackBuilder\BBApplication;
use BackBuilder\Security\Role\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Authorization\Adaptator
 * @copyright   Lp digital system
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
        $this->_roles = $application->getConfig()->getSecurityConfig($section) ?: array();
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
