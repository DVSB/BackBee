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

namespace BackBuilder\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * User object in BackBuilder5
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 * @Entity(repositoryClass="BackBuilder\Security\Repository\UserRepository")
 * @Table(name="user", uniqueConstraints={@UniqueConstraint(name="UNI_LOGIN",columns={"login"})})
 * @fixtures(qty=20)
 */
class User implements UserInterface
{

    /**
     * Unique identifier of the user
     * @var integer
     * @Id @Column(type="integer", name="id")
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $_id;

    /**
     * The login of this user
     * @var string
     * @Column(type="string", name="login")
     * @fixture(type="word")
     */
    protected $_login;

    /**
     * The password of this user
     * @var string
     * @Column(type="string", name="password")
     * @fixture(type="word")
     */
    protected $_password;

    /**
     * The access state
     * @var Boolean
     * @Column(type="boolean", name="activated")
     * @fixture(type="boolean")
     */
    protected $_activated;

    /**
     * The firstame of this user
     * @var string
     * @Column(type="string", name="firstname", nullable=true)
     * @fixture(type="firstName")
     */
    protected $_firstname;

    /**
     * The lastname of this user
     * @var string
     * @Column(type="string", name="lastname", nullable=true)
     * @fixture(type="lastName")
     */
    protected $_lastname;

    /**
     * @var BackBuilder\NestedNode\PageRevision
     * @OneToMany(targetEntity="BackBuilder\NestedNode\PageRevision", mappedBy="_user", fetch="EXTRA_LAZY")
     */
    protected $_revisions;

    /**
     * @ManyToMany(targetEntity="BackBuilder\Security\Group", mappedBy="_users", fetch="EXTRA_LAZY")
     */
    protected $_groups;
    
    /**
     * User's public api key
     * @var String
     * @Column(type="string", name="api_key_public", nullable=true)
     * @fixture(type="string")
     */
    protected $_api_key_public;
    
    /**
     * User's private api key
     * @var String
     * @Column(type="string", name="api_key_private", nullable=true)
     * @fixture(type="string")
     */
    protected $_api_key_private;
    
    /**
     * Whether the api key is enabled (default false)
     * @var Boolean
     * @Column(type="boolean", name="api_key_enabled")
     * @fixture(type="boolean")
     */
    protected $_api_key_enabled = false;

    /**
     * The creation datetime
     * @var \DateTime
     * @Column(type="datetime", name="created")
     */
    protected $_created;

    /**
     * The last modification datetime
     * @var \DateTime
     * @Column(type="datetime", name="modified")
     */
    protected $_modified;
    
    
    

    /**
     * Class constructor
     *
     * @param string $login
     * @param string $password
     * @param string $firstname
     * @param string $lastname
     */
    public function __construct($login = NULL, $password = NULL, $firstname = NULL, $lastname = NULL)
    {
        $this->_login = (is_null($login)) ? '' : $login;
        $this->_password = (is_null($password)) ? '' : $password;
        $this->_firstname = $firstname;
        $this->_lastname = $lastname;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();

        $this->_groups = new ArrayCollection();
        $this->_revisions = new ArrayCollection();
    }

    /**
     * Stringify the user object
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function __toString()
    {
        return trim($this->_firstname . ' ' . $this->_lastname . ' (' . $this->_login . ')');
    }

    /**
     * Serialize the user object
     *
     * @return Json_object
     */
    public function serialize()
    {
        $serialized = new \stdClass();
        $serialized->username = $this->_login;
        $serialized->commonname = trim($this->_firstname . ' ' . $this->_lastname);

        return json_encode($serialized);
    }

    /**
     *
     * @param boolean $bool
     * @return \BackBuilder\Security\User
     * @codeCoverageIgnore
     */
    public function setActivated($bool)
    {
        if (is_bool($bool)) {
            $this->_activated = $bool;
        }
        return $this;
    }

    /**
     *
     * @param string $login
     * @return \BackBuilder\Security\User
     * @codeCoverageIgnore
     */
    public function setLogin($login)
    {
        $this->_login = $login;
        return $this;
    }

    /**
     *
     * @param string $password
     * @return \BackBuilder\Security\User
     * @codeCoverageIgnore
     */
    public function setPassword($password)
    {
        $this->_password = $password;
        return $this;
    }

    /**
     *
     * @param string $firstname
     * @return \BackBuilder\Security\User
     * @codeCoverageIgnore
     */
    public function setFirstname($firstname)
    {
        $this->_firstname = $firstname;
        return $this;
    }

    /**
     *
     * @param string $lastname
     * @return \BackBuilder\Security\User
     * @codeCoverageIgnore
     */
    public function setLastname($lastname)
    {
        $this->_lastname = $lastname;
        return $this;
    }

    /**
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getLogin()
    {
        return $this->_login;
    }

    /**
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFirstname()
    {
        return $this->_firstname;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getLastname()
    {
        return $this->_lastname;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getRevisions()
    {
        return $this->_revisions;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getGroups()
    {
        return $this->_groups;
    }

    /**
     * @param ArrayCollection
     * @return User
     * @codeCoverageIgnore
     */
    public function setGroups(ArrayCollection $groups)
    {
        $this->_groups = $groups;
        return $this;
    }

    /**
     * @param Group $group
     * @return User
     * @codeCoverageIgnore
     */
    public function addGroup(Group $group)
    {
        $this->_groups->add($group);
        return $this;
    }

    /**
     * @return array()
     */
    public function getRoles()
    {
        $roles =  array();
        
        if($this->getApiKeyEnabled()) {
            $roles[] = 'ROLE_API_USER';
        }
        
        return $roles;
    }

    /**
     * @return null
     * @codeCoverageIgnore
     */
    public function getSalt()
    {
        return NULL;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getUsername()
    {
        return $this->getLogin();
    }

    /**
     * @codeCoverageIgnore
     */
    public function eraseCredentials()
    {
        
    }
    
    
    /**
     * 
     * @return string
     * @codeCoverageIgnore
     */
    public function getApiKeyPublic() 
    {
        return $this->_api_key_public;
    }
    
    /**
     * 
     * @param string $api_key_public
     * @return \BackBuilder\Security\User
     * @codeCoverageIgnore
     */
    public function setApiKeyPublic($api_key_public) 
    {
        $this->_api_key_public = $api_key_public;
        return $this;
    }
    
    /**
     * 
     * @return string
     * @codeCoverageIgnore
     */
    public function getApiKeyPrivate() 
    {
        return $this->_api_key_private;
    }
    
    /**
     * 
     * @param string $api_key_private
     * @return \BackBuilder\Security\User
     * @codeCoverageIgnore
     */
    public function setApiKeyPrivate($api_key_private) 
    {
        $this->_api_key_private = $api_key_private;
        return $this;
    }
    
    /**
     * 
     * @return bool
     * @codeCoverageIgnore
     */
    public function getApiKeyEnabled() 
    {
        return $this->_api_key_enabled;
    }
    
    /**
     * 
     * @param bool $api_key_enabled
     * @return \BackBuilder\Security\User
     * @codeCoverageIgnore
     */
    public function setApiKeyEnabled($api_key_enabled) 
    {
        $this->_api_key_enabled = (bool) $api_key_enabled;
        return $this;
    }

}