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

namespace BackBee\Security\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class UsernamePasswordToken extends AbstractToken
{
    private $_credentials;

    /**
     * Constructor.
     *
     * @param array $roles An array of roles
     */
    public function __construct($user, $credentials, array $roles = array())
    {
        parent::__construct($roles);

        $this->setUser($user);
        $this->_credentials = $credentials;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return type
     *              todo Function added: problem with redirection - authentification lost.
     */
    public function isAuthenticated()
    {
        return ($this->getUser() instanceof UserInterface) ? (count($this->getUser()->getRoles()) > 0) : false;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return type
     */
    public function getCredentials()
    {
        return $this->_credentials;
    }

    /**
     * @codeCoverageIgnore
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        parent::eraseCredentials();

        $this->_credentials = null;
    }
}
