<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * @category    BackBee
 * @package     BackBee\Security
 * @copyright   Lp digital system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 * @Entity()
 * @Table(name="`group`", uniqueConstraints={@UniqueConstraint(name="UNI_IDENTIFIER",columns={"id"})})
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Group implements DomainObjectInterface
{
    /**
     * Unique identifier of the group
     * @var integer
     * @Id @Column(type="integer", name="id")
     * @GeneratedValue(strategy="IDENTITY")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnly
     */
    protected $_id;

    /**
     * Group name
     * @var string
     * @Column(type="string", name="name")
     *
     * @Serializer\Expose
     */
    protected $_name;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ManyToMany(targetEntity="BackBee\Security\User", inversedBy="_groups", fetch="EXTRA_LAZY")
     * @JoinTable(
     *      name="user_group",
     *      joinColumns={@JoinColumn(name="group_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     *
     */
    protected $_users;

    /**
     * Optional site.
     * @var \BackBee\Site\Site
     * @ManyToOne(targetEntity="BackBee\Site\Site", fetch="EXTRA_LAZY")
     * @JoinColumn(name="site_uid", referencedColumnName="uid")
     *
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
     * @param  integer                 $id
     * @return \BackBee\Security\Group
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
     * @param  string                  $name
     * @return \BackBee\Security\Group
     */
    public function setName($name)
    {
        $this->_name = $name;

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
     * @param  \Doctrine\Common\Collections\ArrayCollection $users
     * @return \BackBee\Security\Group
     */
    public function setUsers(ArrayCollection $users)
    {
        $this->_users = $users;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param  \BackBee\Security\User  $user
     * @return \BackBee\Security\Group
     */
    public function setUser(User $user)
    {
        $this->_users->add($user);

        return $this;
    }

    /**
     * Returns the optional site
     * @return \BackBee\Site\Site|NULL
     * @codeCoverageIgnore
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * Sets the optional site
     * @param  \BackBee\Site\Site      $site
     * @return \BackBee\Security\Group
     * @codeCoverageIgnore
     */
    public function setSite(\BackBee\Site\Site $site = null)
    {
        $this->_site = $site;

        return $this;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("site_uid")
     *
     * @return string|null
     */
    public function getSiteUid()
    {
        if (null === $this->_site) {
            return;
        }

        return $this->_site->getUid();
    }

    /**
     * @inheritDoc
     */
    public function getObjectIdentifier()
    {
        return $this->getId();
    }
}
