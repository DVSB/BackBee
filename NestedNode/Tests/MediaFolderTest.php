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

namespace BackBuilder\NestedNode\Tests;

use BackBuilder\Tests\TestCase;
use BackBuilder\NestedNode\MediaFolder;

/**
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Tests
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MediaFolderTest extends TestCase
{
    /**
     * @var \Datetime
     */
    private $current_time;

    /**
     * @var \BackBuilder\NestedNode\MediaFolder
     */
    private $folder;

    /**
     * @covers BackBuilder\NestedNode\MediaFolder::__construct
     */
    public function test__construct()
    {
        $this->assertEquals('title', $this->folder->getTitle());
        $this->assertEquals('url', $this->folder->getUrl());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $this->folder->getMedias());

        $folder = new MediaFolder();
        $this->assertEquals('Untitled media folder', $folder->getTitle());
        $this->assertEquals('Url', $folder->getUrl());
    }

    /**
     * @covers BackBuilder\NestedNode\MediaFolder::getTitle
     * @covers BackBuilder\NestedNode\MediaFolder::setTitle
     */
    public function testGetAndSetTitle()
    {
        $this->assertEquals($this->folder, $this->folder->setTitle('new title'));
        $this->assertEquals('new title', $this->folder->getTitle());
    }

    /**
     * @covers BackBuilder\NestedNode\MediaFolder::getUrl
     * @covers BackBuilder\NestedNode\MediaFolder::setUrl
     */
    public function testGetAndSetUrl()
    {
        $this->assertEquals($this->folder, $this->folder->setUrl('new url'));
        $this->assertEquals('new url', $this->folder->getUrl());
    }

    /**
     * @covers BackBuilder\NestedNode\MediaFolder::toArray
     */
    public function testToArray()
    {
        $expected = array(
            'id' => 'node_test',
            'rel' => 'leaf',
            'uid' => 'test',
            'rootuid' => 'test',
            'parentuid' => null,
            'created' => $this->current_time->getTimestamp(),
            'modified' => $this->current_time->getTimestamp(),
            'isleaf' => true,
            'title' => 'title',
            'url' => 'url',
        );

        $this->assertEquals($expected, $this->folder->toArray());
    }

    /**
     * Sets up the fixture
     */
    public function setUp()
    {
        $this->current_time = new \Datetime();
        $this->folder = new MediaFolder('test', 'title', 'url');
    }
}
