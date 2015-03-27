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

use BackBee\ClassContent\Tests\Mock\MockContent;
use BackBee\NestedNode\Media;
use BackBee\NestedNode\MediaFolder;
use BackBee\Tests\TestCase;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MediaTest extends TestCase
{
    /**
     * @var \Datetime
     */
    private $current_time;

    /**
     * @var \BackBee\NestedNode\Media
     */
    private $media;

    /**
     * @covers BackBee\NestedNode\Media::__construct
     */
    public function test__construct()
    {
        $this->assertEquals('media', $this->media->getTitle());
        $this->assertEquals($this->current_time, $this->media->getDate());
        $this->assertEquals($this->current_time, $this->media->getCreated());
        $this->assertEquals($this->current_time, $this->media->getModified());

        $media = new Media(null, new \Datetime('yesterday'));
        $this->assertEquals('Untitled media', $media->getTitle());
        $this->assertEquals(new \Datetime('yesterday'), $media->getDate());
    }

    /**
     * @covers BackBee\NestedNode\Media::setMediaFolder
     * @covers BackBee\NestedNode\Media::getMediaFolder
     */
    public function testSetAndGetMediaFolder()
    {
        $media_folder = new MediaFolder('folder');
        $this->assertEquals($this->media, $this->media->setMediaFolder($media_folder));
        $this->assertEquals($media_folder, $this->media->getMediaFolder());
    }

    /**
     * @covers BackBee\NestedNode\Media::setContent
     * @covers BackBee\NestedNode\Media::getContent
     */
    public function testSetAndGetContent()
    {
        $content = new MockContent();
        $this->assertEquals($this->media, $this->media->setContent($content));
        $this->assertEquals($content, $this->media->getContent());
    }

    /**
     * @covers BackBee\NestedNode\Media::setTitle
     * @covers BackBee\NestedNode\Media::getTitle
     */
    public function testSetAndGetTitle()
    {
        $this->assertEquals($this->media, $this->media->setTitle('test-title'));
        $this->assertEquals('test-title', $this->media->getTitle());
    }

    /**
     * @covers BackBee\NestedNode\Media::setDate
     * @covers BackBee\NestedNode\Media::getDate
     */
    public function testSetAndGetDate()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->assertEquals($this->media, $this->media->setDate($tomorrow));
        $this->assertEquals($tomorrow, $this->media->getDate());
    }

    /**
     * @covers BackBee\NestedNode\Media::setCreated
     * @covers BackBee\NestedNode\Media::getCreated
     */
    public function testSetAndGetCreated()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->assertEquals($this->media, $this->media->setCreated($tomorrow));
        $this->assertEquals($tomorrow, $this->media->GetCreated());
    }

    /**
     * @covers BackBee\NestedNode\Media::setModified
     * @covers BackBee\NestedNode\Media::getModified
     */
    public function testSetAndGetModified()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->assertEquals($this->media, $this->media->setModified($tomorrow));
        $this->assertEquals($tomorrow, $this->media->getModified());
    }

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        $this->current_time = new \Datetime();
        $this->media = new Media('media');
    }
}
