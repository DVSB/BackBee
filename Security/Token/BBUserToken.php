<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Security\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Base class for BackBee token's user
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @subpackage  Token
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class BBUserToken extends AbstractToken
{
    /**
     * Creation date of the token
     * @var \DateTime
     */
    private $_created;

    /**
     * Digest to be checks associated to the token
     * @var string
     */
    private $_digest;

    /**
     * User's private nonce for the token
     * @var string
     */
    private $_nonce;

    /**
     * Class Constructor.
     * @param array $roles An array of roles
     */
    public function __construct(array $roles = array())
    {
        parent::__construct($roles);
        $this->setAuthenticated(true);
    }

    /**
     * Returns the creation date of the token
     * @codeCoverageIgnore
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->_created;
    }

    /**
     * Returns the credentials (empty for this token)
     * @codeCoverageIgnore
     * @return string
     */
    public function getCredentials()
    {
        return '';
    }

    /**
     * Returns the current digest
     * @codeCoverageIgnore
     * @return string
     */
    public function getDigest()
    {
        return $this->_digest;
    }

    /**
     * Returns the user's private nonce
     * @codeCoverageIgnore
     * @return type
     */
    public function getNonce()
    {
        return $this->_nonce;
    }

    /**
     * Sets the creation date
     * @codeCoverageIgnore
     * @param  type                                $created
     * @return \BackBee\Security\Token\BBUserToken
     */
    public function setCreated($created)
    {
        $this->_created = $created;

        return $this;
    }

    /**
     * Sets the digest
     * @codeCoverageIgnore
     * @param  type                                $digest
     * @return \BackBee\Security\Token\BBUserToken
     */
    public function setDigest($digest)
    {
        $this->_digest = $digest;

        return $this;
    }

    /**
     * Sets the user's private nonce
     * @codeCoverageIgnore
     * @param  type                                $nonce
     * @return \BackBee\Security\Token\BBUserToken
     */
    public function setNonce($nonce)
    {
        $this->_nonce = $nonce;

        return $this;
    }

    /**
     * Sets the user
     * @codeCoverageIgnore
     * @param  type                                $user
     * @return \BackBee\Security\Token\BBUserToken
     */
    public function setUser($user)
    {
        parent::setUser($user);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            array(
                is_object($this->getUser()) ? clone $this->getUser() : $this->getUser(),
                $this->isAuthenticated(),
                $this->getRoles(),
                $this->getAttributes(),
                $this->_nonce,
            )
        );
//        $serialized = unserialize(parent::serialize());
//        $serialized[] = $this->_nonce;
//        return serialize($serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $array = unserialize($serialized);
        $this->_nonce = array_pop($array);

        parent::unserialize(serialize($array));
    }
}
