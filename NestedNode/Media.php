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
 * @Entity(repositoryClass="BackBuilder\NestedNode\Repository\MediaRepository")
 * @Table(name="media")
 * @HasLifecycleCallbacks
 */
class Media
{

    /**
     * Unique identifier of the revision
     * @var integer
     * @Id @Column(type="integer", name="id")
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $_id;

    /**
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\MediaFolder", inversedBy="_medias", cascade={"persist"})
     * @JoinColumn(name="media_folder_uid", referencedColumnName="uid")
     */
    protected $_media_folder;

    /**
     * @ManyToOne(targetEntity="BackBuilder\ClassContent\AClassContent", cascade={"persist"})
     * @JoinColumn(name="content_uid", referencedColumnName="uid")
     */
    protected $_content;

    /**
     * The title of this media
     * @var string
     * @Column(type="string", name="title")
     */
    protected $_title;

    /**
     * The publication datetime
     * @var DateTime
     * @Column(type="datetime", name="date")
     */
    protected $_date;

    /**
     * The creation datetime
     * @var DateTime
     * @Column(type="datetime", name="created")
     */
    protected $_created;

    /**
     * The last modification datetime
     * @var DateTime
     * @Column(type="datetime", name="modified")
     */
    protected $_modified;

    /**
     * Class constructor
     */
    public function __construct($title = NULL, $date = NULL)
    {
        $this->_title = (is_null($title)) ? 'Untitled media' : $title;
        $this->_date = (is_null($date)) ? new \DateTime() : $date;

        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();
    }

    public static function getAbsolutePath($content = NULL)
    {
        return __DIR__ . '/../../repository/' . Media::getUploadDir();
    }

    public static function getWebPath($content = NULL)
    {
        return '/images/';
    }

    public static function getUploadTmpDir()
    {
        return __DIR__ . '/../../repository/Data/Tmp/';
    }

    protected static function getUploadDir()
    {
        return 'Data/Media/';
    }

    public function setMediaFolder(\BackBuilder\NestedNode\MediaFolder $media_folder)
    {
        $this->_media_folder = $media_folder;
        return $this;
    }

    public function setContent($content)
    {
        $this->_content = $content;
        return $this;
    }

    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    public function setDate($date)
    {
        $this->_date = $date;
        return $this;
    }

    public function setCreated($created)
    {
        $this->_created = $created;
        return $this;
    }

    public function setModified($modified)
    {
        $this->_modified = $modified;
        return $this;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function getDate()
    {
        return $this->_date;
    }

    public function getCreated()
    {
        return $this->_created;
    }

    public function getModified()
    {
        return $this->_modified;
    }

    public function getMediaFolder()
    {
        return $this->_media_folder;
    }

    public function getContent()
    {
        return $this->_content;
    }

}