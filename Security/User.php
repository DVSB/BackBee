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

namespace BackBee\Security;

use BackBee\Installer\Annotation as BB;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\ORM\Mapping as ORM;

/**
 * User object in BackBee5.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 *
 * @Serializer\ExclusionPolicy("all")
 * @ORM\Entity(repositoryClass="BackBee\Security\Repository\UserRepository")
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="UNI_LOGIN",columns={"login"})})
 * @BB\Fixtures(qty=20)
 */
class User implements UserInterface
{
    const PASSWORD_NOT_PICKED = 0;
    const PASSWORD_PICKED = 0;
    /**
     * Unique identifier of the user.
     *
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Serializer\Expose
     * @Serializer\Type("integer")
     * @Serializer\ReadOnly
     */
    protected $_id;

    /**
     * The login of this user.
     *
     * @var string
     * @ORM\Column(type="string", name="login")
     * @BB\Fixtures(type="word")
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_login;

    /**
     * The login of this user.
     *
     * @var string
     * @ORM\Column(type="string", name="email")
     * @BB\Fixtures(type="email")
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_email;

    /**
     * The raw password of this user.
     *
     * @var string
     */
    protected $_raw_password;

    /**
     * The password of this user.
     *
     * @var string
     * @ORM\Column(type="string", name="password")
     * @BB\Fixtures(type="word")
     *
     * @Serializer\Exclude()
     */
    protected $_password;

    /**
     * The User state.
     *
     * @var Integer
     *
     * @Serializer\Expose
     * @ORM\Column(type="integer", name="state", length=2, options={"default": \BackBee\Security\User::PASSWORD_NOT_PICKED})
     * @Serializer\Type("integer")
     */
    protected $_state = self::PASSWORD_NOT_PICKED;

    /**
     * The access state.
     *
     * @var Boolean
     * @Serializer\Expose
     * @ORM\Column(type="boolean", name="activated")
     * @BB\Fixtures(type="boolean")
     * @Serializer\Type("boolean")
     */
    protected $_activated = false;

    /**
     * The firstame of this user.
     *
     * @var string
     * @Serializer\Expose
     * @ORM\Column(type="string", name="firstname", nullable=true)
     * @BB\Fixtures(type="firstName")
     * @Serializer\Type("string")
     */
    protected $_firstname;

    /**
     * The lastname of this user.
     *
     * @var string
     * @Serializer\Expose
     * @ORM\Column(type="string", name="lastname", nullable=true)
     * @BB\Fixtures(type="lastName")
     * @Serializer\Type("string")
     */
    protected $_lastname;

    /**
     * @var BackBee\NestedNode\PageRevision
     * @ORM\OneToMany(targetEntity="BackBee\NestedNode\PageRevision", mappedBy="_user", fetch="EXTRA_LAZY")
     * @Serializer\Exclude()
     */
    protected $_revisions;

    /**
     * @ORM\ManyToMany(targetEntity="BackBee\Security\Group", mappedBy="_users", fetch="EXTRA_LAZY")
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     * @Serializer\SerializedName("groups")
     * @Serializer\ReadOnly
     */
    protected $_groups;

    /**
     * User's public api key.
     *
     * @var String
     * @Serializer\Expose
     * @ORM\Column(type="string", name="api_key_public", nullable=true)
     * @BB\Fixtures(type="string")
     * @Serializer\Type("string")
     */
    protected $_api_key_public;

    /**
     * User's private api key.
     *
     * @var String
     * @ORM\Column(type="string", name="api_key_private", nullable=true)
     * @BB\Fixtures(type="string")
     * @Serializer\Exclude()
     * @Serializer\Type("string")
     */
    protected $_api_key_private;

    /**
     * Whether the api key is enabled (default false).
     *
     * @var Boolean
     * @Serializer\Expose
     * @ORM\Column(type="boolean", name="api_key_enabled", options={"default": false})
     * @BB\Fixtures(type="boolean")
     * @Serializer\Type("boolean")
     */
    protected $_api_key_enabled = false;

    /**
     * The creation datetime.
     *
     * @var \DateTime
     * @Serializer\Expose
     * @ORM\Column(type="datetime", name="created")
     * @Serializer\Type("DateTime")
     */
    protected $_created;

    /**
     * The last modification datetime.
     *
     * @var \DateTime
     * @Serializer\Expose
     * @ORM\Column(type="datetime", name="modified")
     * @Serializer\Type("DateTime")
     */
    protected $_modified;

    /**
     * Class constructor.
     *
     * @param string $login
     * @param string $password
     * @param string $firstname
     * @param string $lastname
     */
    public function __construct($login = null, $password = null, $firstname = null, $lastname = null)
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
     * Stringify the user object.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function __toString()
    {
        return trim($this->_firstname.' '.$this->_lastname.' ('.$this->_login.')');
    }

    /**
     * Serialize the user object.
     *
     * @return Json_object
     */
    public function serialize()
    {
        $serialized = new \stdClass();
        $serialized->username = $this->_login;
        $serialized->commonname = trim($this->_firstname.' '.$this->_lastname);

        return json_encode($serialized);
    }

    /**
     * @param boolean $bool
     *
     * @return \BackBee\Security\User
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
     * @param string $login
     *
     * @return \BackBee\Security\User
     * @codeCoverageIgnore
     */
    public function setLogin($login)
    {
        $this->_login = $login;

        return $this;
    }

    /**
     * @param string $email
     *
     * @return \BackBee\Security\User
     * @codeCoverageIgnore
     */
    public function setEmail($email)
    {
        $this->_email = $email;

        return $this;
    }

    /**
     * @param string $password
     *
     * @return \BackBee\Security\User
     * @codeCoverageIgnore
     */
    public function setRawPassword($password)
    {
        $this->_raw_password = $password;

        return $this;
    }

    /**
     * @param string $password
     *
     * @return \BackBee\Security\User
     * @codeCoverageIgnore
     */
    public function setPassword($password)
    {
        $this->_password = $password;

        return $this;
    }

    /**
     * @param string $firstname
     *
     * @return \BackBee\Security\User
     * @codeCoverageIgnore
     */
    public function setFirstname($firstname)
    {
        $this->_firstname = $firstname;

        return $this;
    }

    /**
     * @param string $lastname
     *
     * @return \BackBee\Security\User
     * @codeCoverageIgnore
     */
    public function setLastname($lastname)
    {
        $this->_lastname = $lastname;

        return $this;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getLogin()
    {
        return $this->_login;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getRawPassword()
    {
        return $this->_raw_password;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
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
     * @return \BackBee\Security\Group[]
     * @codeCoverageIgnore
     */
    public function getGroups()
    {
        return $this->_groups;
    }

    /**
     * @param ArrayCollection
     *
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
     *
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

        if ($this->getApiKeyEnabled()) {
            $roles[] = 'ROLE_API_USER';
        }

        if ($this->_activated) {
            $roles[] = 'ROLE_ACTIVE_USER';
        }

        return $roles;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getSalt()
    {
        return;
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
     * @return string
     * @codeCoverageIgnore
     */
    public function getApiKeyPublic()
    {
        return $this->_api_key_public;
    }

    /**
     * @param string $api_key_public
     *
     * @return \BackBee\Security\User
     * @codeCoverageIgnore
     */
    public function setApiKeyPublic($api_key_public)
    {
        $this->_api_key_public = $api_key_public;

        return $this;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getApiKeyPrivate()
    {
        return $this->_api_key_private;
    }

    /**
     * @param string $api_key_private
     *
     * @return \BackBee\Security\User
     * @codeCoverageIgnore
     */
    public function setApiKeyPrivate($api_key_private)
    {
        $this->_api_key_private = $api_key_private;

        return $this;
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function getApiKeyEnabled()
    {
        return $this->_api_key_enabled;
    }

    /**
     * @param bool $api_key_enabled
     *
     * @return \BackBee\Security\User
     * @codeCoverageIgnore
     */
    public function setApiKeyEnabled($api_key_enabled)
    {
        $this->_api_key_enabled = (bool) $api_key_enabled;

        return $this;
    }

    /**
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     *
     * @param  bool $api_key_enabled
     * @return \BackBee\Security\User
     * @codeCoverageIgnore
     */
    public function setState($state)
    {
        $this->_state = (int) $state;

        return $this;
    }

    /**
     * Generate an REST api public key based on the private key
     * @return String Rest api public key
     */
    private function generateApiPublicKey()
    {
        return sha1($this->_created->format(\DateTime::ATOM) . $this->_api_key_private);
    }

    /**
     * Generate a random Api pulbic and private key
     * @return void
     */
    public function generateRandomApiKey()
    {
        $this->_api_key_private = md5($this->_id . uniqid());

        $this->_api_key_public = $this->generateApiPublicKey();

        return $this;
    }

    /**
     * Check if the public api key is correct
     * @param  String $public_key public key to check
     * @return Bool               The result of the check
     */
    public function checkPublicApiKey($public_key)
    {
        return ($public_key === $this->generateApiPublicKey());
    }
}
