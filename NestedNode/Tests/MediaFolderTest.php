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

namespace BackBee\NestedNode\Tests;

use BackBee\NestedNode\MediaFolder;
use BackBee\Tests\TestCase;

/**
 * @category    BackBee
 *
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
     * @var \BackBee\NestedNode\MediaFolder
     */
    private $folder;

    /**
     * @covers BackBee\NestedNode\MediaFolder::__construct
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
     * @covers BackBee\NestedNode\MediaFolder::getTitle
     * @covers BackBee\NestedNode\MediaFolder::setTitle
     */
    public function testGetAndSetTitle()
    {
        $this->assertEquals($this->folder, $this->folder->setTitle('new title'));
        $this->assertEquals('new title', $this->folder->getTitle());
    }

    /**
     * @covers BackBee\NestedNode\MediaFolder::getUrl
     * @covers BackBee\NestedNode\MediaFolder::setUrl
     */
    public function testGetAndSetUrl()
    {
        $this->assertEquals($this->folder, $this->folder->setUrl('new url'));
        $this->assertEquals('new url', $this->folder->getUrl());
    }

    /**
     * @covers BackBee\NestedNode\MediaFolder::toArray
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
     * Sets up the fixture.
     */
    public function setUp()
    {
        $this->current_time = new \Datetime();
        $this->folder = new MediaFolder('test', 'title', 'url');
    }
}
