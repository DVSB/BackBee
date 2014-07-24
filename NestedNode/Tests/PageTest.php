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

use BackBuilder\NestedNode\Page;

/**
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Tests
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageTest extends ANestedNodeTest
{

    /**
     * @covers BackBuilder\NestedNode\Page::__construct
     */
    public function test__construct()
    {
        $page = new Page();

        $this->assertInstanceOf('BackBuilder\ClassContent\ContentSet', $page->getContentSet());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $page->getRevisions());
        $this->assertEquals(Page::STATE_HIDDEN, $page->getState());
        $this->assertFalse($page->isStatic());
        $this->assertEquals(Page::DEFAULT_TARGET, $page->getTarget());

        parent::test__construct();
    }

    /**
     * @covers BackBuilder\NestedNode\Page::__construct
     */
    public function test__constructWithOptions()
    {
        $page = new Page('test', array('title' => 'title', 'url' => 'url'));
        $this->assertEquals('title', $page->getTitle());
        $this->assertEquals('url', $page->getUrl());

        $pagef = new Page('test', 'not an array');
        $this->assertNull($pagef->getTitle());
        $this->assertNull($pagef->getUrl());
    }

    /**
     * Sets up the fixture
     */
    public function setUp()
    {
        parent::setUp();
    }

}
