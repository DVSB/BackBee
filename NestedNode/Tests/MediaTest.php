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
use BackBuilder\NestedNode\Media;
use BackBuilder\NestedNode\MediaFolder;
use BackBuilder\ClassContent\Tests\Mock\MockContent;

/**
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Tests
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
     * @var \BackBuilder\NestedNode\Media
     */
    private $media;

    /**
     * @covers BackBuilder\NestedNode\Media::__construct
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
     * @covers BackBuilder\NestedNode\Media::setMediaFolder
     * @covers BackBuilder\NestedNode\Media::getMediaFolder
     */
    public function testSetAndGetMediaFolder()
    {
        $media_folder = new MediaFolder('folder');
        $this->assertEquals($this->media, $this->media->setMediaFolder($media_folder));
        $this->assertEquals($media_folder, $this->media->getMediaFolder());
    }

    /**
     * @covers BackBuilder\NestedNode\Media::setContent
     * @covers BackBuilder\NestedNode\Media::getContent
     */
    public function testSetAndGetContent()
    {
        $content = new MockContent();
        $this->assertEquals($this->media, $this->media->setContent($content));
        $this->assertEquals($content, $this->media->getContent());
    }

    /**
     * @covers BackBuilder\NestedNode\Media::setTitle
     * @covers BackBuilder\NestedNode\Media::getTitle
     */
    public function testSetAndGetTitle()
    {
        $this->assertEquals($this->media, $this->media->setTitle('test-title'));
        $this->assertEquals('test-title', $this->media->getTitle());
    }

    /**
     * @covers BackBuilder\NestedNode\Media::setDate
     * @covers BackBuilder\NestedNode\Media::getDate
     */
    public function testSetAndGetDate()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->assertEquals($this->media, $this->media->setDate($tomorrow));
        $this->assertEquals($tomorrow, $this->media->getDate());
    }

    /**
     * @covers BackBuilder\NestedNode\Media::setCreated
     * @covers BackBuilder\NestedNode\Media::getCreated
     */
    public function testSetAndGetCreated()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->assertEquals($this->media, $this->media->setCreated($tomorrow));
        $this->assertEquals($tomorrow, $this->media->GetCreated());
    }

    /**
     * @covers BackBuilder\NestedNode\Media::setModified
     * @covers BackBuilder\NestedNode\Media::getModified
     */
    public function testSetAndGetModified()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->assertEquals($this->media, $this->media->setModified($tomorrow));
        $this->assertEquals($tomorrow, $this->media->getModified());
    }

    /**
     * Sets up the fixture
     */
    public function setUp()
    {
        $this->current_time = new \Datetime();
        $this->media = new Media('media');
    }
}
