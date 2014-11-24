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

use BackBuilder\Site\Site;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Section object in BackBuilder
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode
 * @author      Micael Malta <mmalta@nextinteractive.fr>
 * @Entity(repositoryClass="BackBuilder\NestedNode\Repository\SectionRepository")
 * @Table(name="section",indexes={@index(columns={"uid", "root_uid", "leftnode", "rightnode"})})
 * @HasLifecycleCallbacks
 */
class Section extends ANestedNode
{

    /**
     * Unique identifier of the section
     * @var string
     * @Id @Column(type="string", name="uid", nullable=false)
     * @fixture(type="md5")
     */
    protected $_uid;

    /**
     * The root node, cannot be NULL.
     * @var \BackBuilder\NestedNode\Section
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\Section", inversedBy="_descendants", fetch="EXTRA_LAZY")
     * @JoinColumn(name="root_uid", referencedColumnName="uid", nullable=false)
     */
    protected $_root;

    /**
     * The parent node.
     * @var \BackBuilder\NestedNode\Section
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\Section", inversedBy="_children", fetch="EXTRA_LAZY")
     * @JoinColumn(name="parent_uid", referencedColumnName="uid", nullable=true)
     */
    protected $_parent;

    /**
     * Descendants nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\NestedNode\Section", mappedBy="_root", fetch="EXTRA_LAZY")
     */
    protected $_descendants;

    /**
     * Direct children nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\NestedNode\Section", mappedBy="_parent", fetch="EXTRA_LAZY")
     */
    protected $_children;

    /**
     * The associated page of this section
     * @var \BackBuilder\NestedNode\Page
     * @OneToOne(targetEntity="BackBuilder\NestedNode\Page", fetch="EXTRA_LAZY")
     * @JoinColumn(name="uid", referencedColumnName="uid")
     */
    protected $_page;

    /**
     * Store pages using this section.
     * var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\NestedNode\Page", mappedBy="_section", fetch="EXTRA_LAZY")
     */
    protected $_pages;

    /**
     * The owner site of this section
     * @var \BackBuilder\Site\Site
     * @ManyToOne(targetEntity="BackBuilder\Site\Site", fetch="EXTRA_LAZY")
     * @JoinColumn(name="site_uid", referencedColumnName="uid", nullable=false)
     */
    protected $_site;

    /**
     * Class constructor
     * @param string $uid The unique identifier of the section
     * @param array $options Initial options for the section:
     *                         - page      the associated page
     *                         - site      the owning site
     */
    public function __construct($uid = null, $options = null)
    {
        parent::__construct($uid, $options);

        if (
                true === is_array($options) &&
                true === array_key_exists('page', $options) &&
                $options['page'] instanceof Page
        ) {
            $this->setPage($options['page']);
        }

        if (
                true === is_array($options) &&
                true === array_key_exists('site', $options) &&
                $options['site'] instanceof Site
        ) {
            $this->setSite($options['site']);
        }

        $this->_pages = new ArrayCollection();
    }

    /**
     * Sets the associated page for this section
     * @param \BackBuilder\NestedNode\Page $page
     * @return \BackBuilder\NestedNode\Section
     */
    public function setPage(Page $page)
    {
        $this->_page = $page;
        $this->_uid = $page->getUid();
        $page->setMainSection($this);

        return $this;
    }

    /**
     * Returns the associated page this section
     * @return \BackBuilder\NestedNode\Page
     * @codeCoverageIgnore
     */
    public function getPage()
    {
        return $this->_page;
    }

    /**
     * Returns the owning pages
     * @return \Doctrine\Common\Collections\ArrayCollection
     * @codeCoverageIgnore
     */
    public function getPages()
    {
        return $this->_pages;
    }

    /**
     * Sets the site of this section
     * @param \BackBuilder\Site\Site $site
     * @return \BackBuilder\NestedNode\Section
     */
    public function setSite(Site $site)
    {
        $this->_site = $site;
        return $this;
    }

    /**
     * Returns the site of this section
     * @return \BackBuilder\Site\Site
     * @codeCoverageIgnore
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * Returns an array representation of the node
     * @return string
     */
    public function toArray()
    {
        $result = parent::toArray();
        $result['siteuid'] = (null !== $this->getSite()) ? $this->getSite()->getUid() : null;

        return $result;
    }

    /**
     * A section is never a leaf
     * @return Boolean always FALSE
     */
    public function isLeaf()
    {
        return false;
    }

}
