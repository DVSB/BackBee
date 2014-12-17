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

namespace BackBee\Site;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * UserPreferences object in BackBee 5
 *
 * User preferences persistence
 *
 * @category    BackBee
 * @package     BackBee\Site
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 * @Entity(repositoryClass="BackBee\Site\Repository\UserPreferencesRepository")
 * @Table(name="user_preferences",indexes={@index(name="IDX_OWNER", columns={"owner"})})
 */
class UserPreferences
{
    /**
     * Unique identifier of the revision
     * @var string
     * @Id @Column(type="string", name="uid")
     */
    private $_uid;

    /**
     * The owner of this revision
     * @var Symfony\Component\Security\Acl\Domain\UserSecurityIdentity
     *
     * @Column(type="string", name="owner")
     */
    private $_owner;

    /**
     * The owner of this revision
     * @var text
     *
     * @Column(type="text", name="preferences")
     */
    private $_preferences;

    /**
     * Class constructor
     *
     * @param string         $uid   The unique identifier of the revision
     * @param TokenInterface $token The current auth token
     */
    public function __construct($uid = null, $token = null)
    {
        $this->_uid = (is_null($uid)) ? md5(uniqid('', true)) : $uid;

        if ($token instanceof TokenInterface) {
            $this->_owner = UserSecurityIdentity::fromToken($token);
        }
    }

    /**
     * Return the uid user preferences
     * @codeCoverageIgnore
     * @return String
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Return the owner of the user preferences
     * @codeCoverageIgnore
     * @return Symfony\Component\Security\Acl\Domain\UserSecurityIdentity
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * Return a json encoded string of user preferences
     * @codeCoverageIgnore
     * @return text
     */
    public function getPreferences()
    {
        return $this->_preferences;
    }

    /**
     * Set the user preferences UID
     * @codeCoverageIgnore
     * @param  string                            $uid
     * @return \BackBee\Site\UserPreferences
     */
    public function setUid($uid)
    {
        $this->_uid = $uid;

        return $this;
    }

    /**
     * Set the owner of the user preferences
     * @codeCoverageIgnore
     * @param  \Symfony\Component\Security\Core\User\UserInterface $user
     * @return \BackBee\Site\UserPreferences
     */
    public function setOwner(UserInterface $user)
    {
        $this->_owner = UserSecurityIdentity::fromAccount($user);

        return $this;
    }

    /**
     * Set the preferences of the user
     *
     * @param  mixed                             $preferences
     * @return \BackBee\Site\UserPreferences
     */
    public function setPreferences($preferences)
    {
        if (is_array($preferences) || is_object($preferences)) {
            $preferences = json_encode($preferences);
        }
        $this->_preferences = $preferences;

        return $this;
    }
}
