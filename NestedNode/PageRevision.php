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

namespace BackBuilder\NestedNode;

/**
 * PageRevison object in BackBuilder 4
 * 
 * A page revision is...
 * 
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 * @Entity(repositoryClass="BackBuilder\NestedNode\Repository\PageRevisionRepository")
 * @Table(name="page_revision")
 */
class PageRevision
{
    /**
     * Versions
     */

    const VERSION_CURRENT = 0;
    const VERSION_DRAFT = 1;
    const VERSION_SUBMITED = 2;

    /**
     * Unique identifier of the revision
     * @var integer
     * @Id @Column(type="integer", name="id")
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $_id;

    /**
     * The publication datetime
     * @var DateTime
     * @Column(type="datetime", name="date")
     */
    protected $_date;

    /**
     * The version
     * @var DateTime
     * @Column(type="integer", name="version")
     */
    protected $_version;

    /**
     * @ManyToOne(targetEntity="BackBuilder\Security\User", inversedBy="_revisions")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $_user;

    /**
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\Page", inversedBy="_revisions")
     * @JoinColumn(name="page_uid", referencedColumnName="uid")
     */
    protected $_page;

    /**
     * @ManyToOne(targetEntity="BackBuilder\ClassContent\AClassContent", cascade={"persist"})
     * @JoinColumn(name="content_uid", referencedColumnName="uid")
     */
    protected $_content;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_date = new \DateTime();
        $this->_version = PageRevision::VERSION_DRAFT;
    }

    public function setUser(\BackBuilder\Security\User $user)
    {
        $this->_user = $user;
        return $this;
    }

    public function setPage(\BackBuilder\NestedNode\Page $page)
    {
        $this->_page = $page;
        return $this;
    }

    public function setContent($content)
    {
        $this->_content = $content;
        return $this;
    }

    public function setDate($date)
    {
        $this->_date = $date;
        return $this;
    }

    public function setVersion($version)
    {
        $this->_version = $version;
        return $this;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getUser()
    {
        return $this->_user;
    }

    public function getPage()
    {
        return $this->_page;
    }

    public function getContent()
    {
        return $this->_content;
    }

    public function getDate()
    {
        return $this->_date;
    }

    public function getVersion()
    {
        return $this->_version;
    }

}