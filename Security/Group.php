<?php

namespace BackBuilder\Security;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 * @Entity()
 * @Table(name="groups")
 */
class Group
{

    /**
     * Unique identifier of the group
     * @var integer
     * @Id @Column(type="integer", name="id")
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $_id;

    /**
     * Group name
     * @var string
     * @Column(type="string", name="name")
     */
    protected $_name;

    /**
     * Group name identifier
     * @var string
     * @Column(type="string", name="identifier")
     */
    protected $_identifier;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ManyToMany(targetEntity="BackBuilder\Security\User", inversedBy="_groups")
     * @JoinTable(
     *      name="user_group",
     *      joinColumns={@JoinColumn(name="group_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    protected $_users;

    /**
     * Optional site.
     * @var \BackBuilder\Site\Site
     * @ManyToOne(targetEntity="BackBuilder\Site\Site")
     * @JoinColumn(name="site_uid", referencedColumnName="uid")
     */
    protected $_site;

    /**
     * @codeCoverageIgnore 
     */
    public function __construct()
    {
        $this->_users = new ArrayCollection();
    }

    /**
     * @codeCoverageIgnore
     * @return integer
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @codeCoverageIgnore
     * @param integer $id
     * @return \BackBuilder\Security\Group
     */
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @codeCoverageIgnore
     * @param string $name
     * @return \BackBuilder\Security\Group
     */
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * @codeCoverageIgnore
     * @param string $identifier
     * @return \BackBuilder\Security\Group
     */
    public function setIdentifier($identifier)
    {
        $this->_identifier = $identifier;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getUsers()
    {
        return $this->_users;
    }

    /**
     * @codeCoverageIgnore
     * @param \Doctrine\Common\Collections\ArrayCollection $users
     * @return \BackBuilder\Security\Group
     */
    public function setUsers(ArrayCollection $users)
    {
        $this->_users = $users;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param \BackBuilder\Security\User $user
     * @return \BackBuilder\Security\Group
     */
    public function setUser(User $user)
    {
        $this->_users->add($user);
        return $this;
    }

    /**
     * Returns the optional site
     * @return \BackBuilder\Site\Site|NULL
     * @codeCoverageIgnore
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * Sets the optional site
     * @param \BackBuilder\Site\Site $site
     * @return \BackBuilder\Security\Group
     * @codeCoverageIgnore
     */
    public function setSite(\BackBuilder\Site\Site $site = null)
    {
        $this->_site = $site;
        return $this;
    }

}
