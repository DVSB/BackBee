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

use BackBuilder\NestedNode\ANestedNode;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Media folder object in BackBuilder
 * 
 * A media folder is...
 * 
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 * @Entity(repositoryClass="BackBuilder\NestedNode\Repository\MediaFolderRepository")
 * @Table(name="media_folder",indexes={@index(name="IDX_ROOT", columns={"root_uid"}), @index(name="IDX_PARENT", columns={"parent_uid"}), @index(name="IDX_SELECT_MEDIAFOLDER", columns={"root_uid", "leftnode", "rightnode"})})
 */
class MediaFolder extends ANestedNode
{

    /**
     * Unique identifier of the content
     * @var string
     * @Id @Column(type="string", name="uid")
     */
    protected $_uid;

    /**
     * The root node, cannot be NULL.
     * @var \BackBuilder\NestedNode\MediaFolder
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\MediaFolder", inversedBy="_descendants")
     * @JoinColumn(name="root_uid", referencedColumnName="uid")
     */
    protected $_root;

    /**
     * The parent node.
     * @var \BackBuilder\NestedNode\MediaFolder
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\MediaFolder", inversedBy="_children", cascade={"persist"})
     * @JoinColumn(name="parent_uid", referencedColumnName="uid")
     */
    protected $_parent;

    /**
     * The title of this media folder
     * @var string
     * @Column(type="string", name="title")
     */
    protected $_title;

    /**
     * The URI of this media folder
     * @var string
     * @Column(type="string", name="url")
     */
    protected $_url;

    /**
     * Descendants nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\NestedNode\MediaFolder", mappedBy="_root")
     */
    protected $_descendants;

    /**
     * Direct children nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\NestedNode\MediaFolder", mappedBy="_parent")
     */
    protected $_children;

    /**
     * A collection of medi stored in the folder
     * @var \BackBuilder\NestedNode\Media
     * @OneToMany(targetEntity="BackBuilder\NestedNode\Media", mappedBy="_media_folder")
     */
    protected $_medias;

    /**
     * Class constructor
     * @param string $uid
     * @param string $title
     * @param string $url
     */
    public function __construct($uid = NULL, $title = NULL, $url = NULL)
    {
        parent::__construct($uid);

        $this->_title = (is_null($title)) ? 'Untitled media folder' : $title;
        $this->_url = (is_null($url)) ? 'Url' : $url;

        $this->_medias = new ArrayCollection();
    }

    /**
     * Returns the title
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Returns the URL of the media folder
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * Returns a collection of media
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getMedias()
    {
        return $this->_medias;
    }

    /**
     * Returns an array representation of the media folder.
     * @return array
     */
    public function toArray()
    {
        $result = parent::toArray();
        $result['title'] = $this->getTitle();
        $result['url'] = $this->getUrl();

        return $result;
    }

    /**
     * Sets the title.
     * @param string $title
     * @return \BackBuilder\NestedNode\MediaFolder
     */
    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    /**
     * Sets the URL
     * @param type $url
     * @return \BackBuilder\NestedNode\MediaFolder
     */
    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }

}