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

namespace BackBuilder\Security\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken,
    Symfony\Component\Security\Core\User\UserInterface,
    Symfony\Component\HttpFoundation\Request;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Token
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class PublicKeyToken extends AbstractToken
{
    
    /**
     *
     * @var string
     */
    public $publicKey;
    
    /**
     *
     * @var string
     */
    public $signature;
    
    /**
     *
     * @var Request
     */
    public $request;
    
    /**
     * Constructor.
     * @param array  $roles       An array of roles
     */
    public function __construct(array $roles = array())
    {
        parent::__construct($roles);

        $this->setAuthenticated(count($roles) > 0);
    }

    /**
     * @codeCoverageIgnore
     * @return type
     * todo Function added: problem with redirection - authentification lost.
     */
    public function isAuthenticated()
    {
        return ($this->getUser() instanceof UserInterface) ? (count($this->getUser()->getRoles()) > 0) : false;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getCredentials()
    {
        return '';
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
    
    
    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->getUser()->getApiKeyPublic();
        }

        return (string) $this->getUser();
    }

}