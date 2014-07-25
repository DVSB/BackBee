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

use BackBuilder\ClassContent\ContentSet,
    BackBuilder\NestedNode\Page,
    BackBuilder\Site\Layout,
    BackBuilder\Site\Site;

/**
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Tests
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageTest extends ANestedNodeTest
{

    /**
     * @var \BackBuilder\NestedNode\Page
     */
    private $page;

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
        $this->assertEquals('title', $this->page->getTitle());
        $this->assertEquals('url', $this->page->getUrl());

        $pagef = new Page('test', 'not an array');
        $this->assertNull($pagef->getTitle());
        $this->assertNull($pagef->getUrl());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::__clone
     */
    public function test__clone()
    {
        $child = new Page('child', array('title' => 'child', 'url' => 'url'));
        $child->setRoot($this->page)
                ->setParent($this->page)
                ->setLeftnode(2)
                ->setRightnode(3)
                ->setState(Page::STATE_ONLINE);

        $clone = clone $child;
        $this->assertNotEquals($child->getContentSet(), $clone->getContentSet());
        $this->assertNotEquals($child->getUid(), $clone->getUid());
        $this->assertEquals(1, $clone->getLeftnode());
        $this->assertEquals(2, $clone->getRightnode());
        $this->assertEquals(0, $clone->getLevel());
        $this->assertEquals($this->current_time, $clone->getCreated());
        $this->assertEquals($this->current_time, $clone->getModified());
        $this->assertNull($clone->getParent());
        $this->assertEquals($clone, $clone->getRoot());
        $this->assertEquals(Page::STATE_OFFLINE, $clone->getState());
        $this->assertFalse($clone->isStatic());
        $this->assertEquals($child->getTitle(), $clone->getTitle());
        $this->assertEquals($child->getUrl(), $clone->getUrl());
        $this->assertTrue(is_array($clone->cloning_datas));
        $this->assertTrue(isset($clone->cloning_datas['pages']));
        $this->assertTrue(isset($clone->cloning_datas['pages'][$child->getUid()]));
        $this->assertEquals($clone, $clone->cloning_datas['pages'][$child->getUid()]);

        $clone2 = clone $this->page;
        $this->assertEquals($this->page->getLayout(), $clone2->getLayout());
        $this->assertNotEquals($this->page->getContentSet(), $clone2->getContentSet());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::getContentSet
     */
    public function testGetContentSet()
    {
        $this->assertInstanceOf('BackBuilder\ClassContent\ContentSet', $this->page->getContentSet());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::getUrl
     */
    public function testGetUrl()
    {
        $this->assertEquals('url', $this->page->getUrl());

        $this->page->setRedirect('redirect');
        $this->assertEquals('redirect', $this->page->getUrl());
        $this->assertEquals('redirect', $this->page->getUrl(true));
        $this->assertEquals('url', $this->page->getUrl(false));
    }

    /**
     * @covers BackBuilder\NestedNode\Page::getNormalizeUri
     */
    public function testGetNormalizeUri()
    {
        $this->assertEquals('url', $this->page->getNormalizeUri());

        $site = new Site();
        $this->page->setSite($site);
        $this->assertEquals('url.html', $this->page->getNormalizeUri());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::getTarget
     */
    public function testGetTarget()
    {
        $this->assertEquals(Page::DEFAULT_TARGET, $this->page->getTarget());

        $this->page->setTarget('target');
        $this->assertEquals('target', $this->page->getTarget());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::isRedirect
     */
    public function testIsRedirect()
    {
        $this->assertFalse($this->page->isRedirect());

        $this->page->setRedirect('redirect');
        $this->assertTrue($this->page->isRedirect());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::getRevisions
     */
    public function testGetRevisions()
    {
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $this->page->getRevisions());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::getData
     */
    public function testGetData()
    {
        $this->assertEquals($this->page->toArray(), $this->page->getData());
        $this->assertEquals('title', $this->page->getData('title'));
        $this->assertNull($this->page->getData('unknown'));
    }

    /**
     * @covers BackBuilder\NestedNode\Page::getParam
     */
    public function testGetParam()
    {
        $params = array(
            'left' => $this->page->getLeftnode(),
            'right' => $this->page->getRightnode(),
            'level' => $this->page->getLevel()
        );

        $this->assertEquals($params, $this->page->getParam());
        $this->assertEquals($this->page->getLeftnode(), $this->page->getParam('left'));
        $this->assertEquals($this->page->getRightnode(), $this->page->getParam('right'));
        $this->assertEquals($this->page->getLevel(), $this->page->getParam('level'));
        $this->assertNull($this->page->getParam('unknown'));
    }

    /**
     * @covers BackBuilder\NestedNode\Page::isScheduled
     */
    public function testIsScheduled()
    {
        $this->assertFalse($this->page->isScheduled());

        $this->page->setPublishing(new \DateTime());
        $this->page->setArchiving();
        $this->assertTrue($this->page->isScheduled());

        $this->page->setPublishing();
        $this->page->setArchiving(new \DateTime());
        $this->assertTrue($this->page->isScheduled());

        $this->page->setPublishing(new \DateTime());
        $this->page->setArchiving(new \DateTime());
        $this->assertTrue($this->page->isScheduled());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::isVisible
     */
    public function testIsVisble()
    {
        $this->assertFalse($this->page->isVisible());

        $this->page->setState(Page::STATE_ONLINE);
        $this->assertTrue($this->page->isVisible());

        $this->page->setState(Page::STATE_ONLINE & Page::STATE_HIDDEN);
        $this->assertFalse($this->page->isVisible());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::isOnline
     */
    public function testIsOnline()
    {
        $this->assertFalse($this->page->isOnline());
        $this->assertFalse($this->page->isOnline(true));

        $this->page->setState(Page::STATE_ONLINE);
        $this->assertTrue($this->page->isOnline());
        $this->assertTrue($this->page->isOnline(true));

        $this->page->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
        $this->assertTrue($this->page->isOnline());
        $this->assertTrue($this->page->isOnline(true));

        $this->page->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN + Page::STATE_DELETED);
        $this->assertFalse($this->page->isOnline());
        $this->assertFalse($this->page->isOnline(true));

        $yesterday = new \DateTime('yesterday');
        $tomorrow = new \DateTime('tomorrow');

        $this->page->setState(Page::STATE_ONLINE)
                ->setPublishing($tomorrow)
                ->setArchiving();
        $this->assertFalse($this->page->isOnline());
        $this->assertTrue($this->page->isOnline(true));

        $this->page->setPublishing()
                ->setArchiving($yesterday);
        $this->assertFalse($this->page->isOnline());
        $this->assertTrue($this->page->isOnline(true));

        $this->page->setPublishing($yesterday)
                ->setArchiving($tomorrow);
        $this->assertTrue($this->page->isOnline());
        $this->assertTrue($this->page->isOnline(true));
    }

    /**
     * @covers BackBuilder\NestedNode\Page::isDeleted
     */
    public function testIsDeleted()
    {
        $this->assertFalse($this->page->isDeleted());

        $this->page->setState($this->page->getState() + Page::STATE_DELETED);
        $this->assertTrue($this->page->isDeleted());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::setSite
     */
    public function testSetSite()
    {
        $site = new Site();
        $this->assertEquals($this->page, $this->page->setSite($site));
        $this->assertEquals($site, $this->page->getSite());

        $this->page->setSite();
        $this->assertNull($this->page->getSite());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::setContentSet
     */
    public function testSetContentSet()
    {
        $contentset = new ContentSet();
        $this->assertEquals($this->page, $this->page->setContentSet($contentset));
        $this->assertEquals($contentset, $this->page->getContentSet());
    }

    /**
     * @covers BackBuilder\NestedNode\Page::setDate
     */
    public function testSetDate()
    {
        $this->assertEquals($this->page, $this->page->setDate($this->current_time));
        $this->assertEquals($this->current_time, $this->page->getDate());
    }

    /**
     * Sets up the fixtureyout()
     */
    public function setUp()
    {
        parent::setUp();
        $this->page = new Page('test', array('title' => 'title', 'url' => 'url'));
        $this->page->setLayout(new Layout());
    }

}
