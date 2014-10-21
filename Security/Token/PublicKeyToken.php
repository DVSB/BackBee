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

use BackBuilder\Security\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Token
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class PublicKeyToken extends BBUserToken
{
    /**
     *
     * @var string
     */
    private $publicKey;

    /**
     *
     * @var string
     */
    private $signature;

    /**
     * Constructor.
     *
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
        return ($this->getUser() instanceof UserInterface)
            ? 0 < count($this->getUser()->getRoles())
            : false
        ;
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
        $username = '';
        if ($this->getUser() instanceof User) {
            $username = $this->getUser()->getApiKeyPublic();
        } elseif ($username instanceof UserInterface) {
            $username = $this->getUser()->getUsername();
        } else {
            $username = (string) $this->getUser();
        }

        return $username;
    }

    /**
     * Public key attribute setter
     *
     * @param string $signature new public key value
     *
     * @return self
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    /**
     * Public key attribute getter
     *
     * @return string the current token public key
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Signature attribute setter
     *
     * @param string $signature new signature value
     *
     * @return self
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Signature attribute getter
     *
     * @return string the current token signature
     */
    public function getSignature()
    {
        return $this->signature;
    }
}
