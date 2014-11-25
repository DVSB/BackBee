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

use BackBuilder\NestedNode\Section;
use BackBuilder\NestedNode\Page;
use BackBuilder\Site\Site;
use BackBuilder\Tests\TestCase;

/**
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Tests
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class SectionTest extends TestCase
{

    /**
     * @var \Datetime
     */
    private $current_time;

    /**
     * @var \BackBuilder\NestedNode\Section
     */
    private $section;

    /**
     * @covers BackBuilder\NestedNode\Section::__construct
     */
    public function test__construct()
    {
        $section = new Section();

        $this->assertNull($section->getPage());
        $this->assertNull($section->getSite());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $section->getPages());
    }

    /**
     * @covers BackBuilder\NestedNode\Section::__construct
     */
    public function test__constructWithOptions()
    {
        $page = new Page();
        $site = new Site();
        $section = new Section(null, array('page' => $page, 'site' => $site));

        $this->assertEquals($page, $section->getPage());
        $this->assertEquals($site, $section->getSite());
    }

    /**
     * @covers BackBuilder\NestedNode\Section::setPage
     */
    public function testSetPage()
    {
        $section = new Section();
        $page = new Page();

        $this->assertEquals($section, $section->setPage($page));
        $this->assertEquals($page, $section->getPage());
    }

    /**
     * @covers BackBuilder\NestedNode\Section::setSite
     */
    public function testSetSite()
    {
        $section = new Section();
        $site = new Site();

        $this->assertEquals($section, $section->setSite($site));
        $this->assertEquals($site, $section->getSite());
    }

    /**
     * @covers BackBuilder\NestedNode\Section::toArray
     */
    public function testToArray()
    {
        $current_time = new \Datetime();

        $expected = array(
            'id' => 'node_test',
            'rel' => 'folder',
            'uid' => 'test',
            'rootuid' => 'test',
            'parentuid' => null,
            'created' => $current_time->getTimestamp(),
            'modified' => $current_time->getTimestamp(),
            'isleaf' => false,
            'siteuid' => null
        );

        $section = new Section('test');
        $this->assertEquals($expected, $section->toArray());

        $section->setSite(new Site('site_test'));
        $expected['siteuid'] = 'site_test';
        $this->assertEquals($expected, $section->toArray());
    }

    /**
     * @covers BackBuilder\NestedNode\Section::isLeaf
     */
    public function testIsLeaf()
    {
        $section = new Section();
        $this->assertFalse($section->isLeaf());
    }

}
