<?php

namespace BackBuilder\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * User object in BackBuilder 4
 *
 * A user is...
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp system
 * @author      m.baptista
 * @Entity(repositoryClass="BackBuilder\Security\Repository\UserRepository")
 * @Table(name="user")
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
     */
    protected $_login;

    /**
     * The password of this user
     * @var string
     * @Column(type="string", name="password")
     */
    protected $_password;

    /**
     * The access state
     * @var Boolean
     * @Column(type="boolean", name="activated")
     */
    protected $_activated;

    /**
     * The firstame of this user
     * @var string
     * @Column(type="string", name="firstname")
     */
    protected $_firstname;

    /**
     * The lastname of this user
     * @var string
     * @Column(type="string", name="lastname")
     */
    protected $_lastname;

    /**
     * @var BackBuilder\NestedNode\PageRevision
     * @OneToMany(targetEntity="BackBuilder\NestedNode\PageRevision", mappedBy="_user")
     */
    protected $_revisions;

    /**
     * @ManyToMany(targetEntity="BackBuilder\Security\Group", mappedBy="_users")
     */
    protected $_groups;

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
     * @codeCoverageIgnore
     */
    public function getRoles()
    {
        return array();
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

}