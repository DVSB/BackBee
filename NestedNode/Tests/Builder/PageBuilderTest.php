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

namespace BackBee\NestedNode\Tests\Repository;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Tests\Mock\MockContent;
use BackBee\NestedNode\Builder\PageBuilder;
use BackBee\NestedNode\Page;
use BackBee\Site\Site;
use BackBee\Tests\BackBeeTestCase;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageBuilderTest extends BackBeeTestCase
{

    /**
     * @var \BackBee\NestedNode\Builder\PageBuilder
     */
    private static $builder;

    /**
     * @var \BackBee\NestedNode\Repository\PageRepository
     */
    private $repository;

    /**
     * @var \BackBee\Site\Site
     */
    private $site;

    /**
     * @var \BackBee\Site\Layout
     */
    private $layout;

    /**
     * @var \BackBee\NestedNode\Page
     */
    private $root;

    /**
     * Test constructor.
     *
     * @param string    $name
     * @param array     $data
     * @param string    $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->repository = self::$em->getRepository('BackBee\NestedNode\Page');
    }

    /**
     * Prepare database with one site and one layout for the following tests
     */
    public static function setUpBeforeClass()
    {
        self::$kernel->resetDatabase(null, true);
        self::$builder = new PageBuilder(self::$em);

        $site = new Site('site-test', ['label' => 'site-test']);
        $layout = self::$kernel->createLayout('layout-test', 'layout-test');

        $root = self::$builder
                ->setUid('root')
                ->setTitle('root')
                ->setSite($site)
                ->setLayout($layout)
                ->setPersistMode(PageBuilder::PERSIST_AS_FIRST_CHILD)
                ->getPage();

        self::$em->persist($site);
        self::$em->persist($layout);
        self::$em->persist($root);
        self::$em->flush();
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        $this->site = self::$em->find('BackBee\Site\Site', 'site-test');
        $this->layout = self::$em->find('BackBee\Site\Layout', 'layout-test');
        $this->root = self::$em->find('BackBee\NestedNode\Page', 'root');
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::__construct
     * @covers \BackBee\NestedNode\Builder\PageBuilder::reset
     */
    public function testReset()
    {
        $this->assertNull(self::$builder->getUid());
        $this->assertNull(self::$builder->getTitle());
        $this->assertNull(self::$builder->getAltTitle());
        $this->assertNull(self::$builder->getRedirect());
        $this->assertNull(self::$builder->getTarget());
        $this->assertNull(self::$builder->getUrl());
        $this->assertNull(self::$builder->getSite());
        $this->assertNull(self::$builder->getRoot());
        $this->assertNull(self::$builder->getParent());
        $this->assertNull(self::$builder->getLayout());
        $this->assertEquals([], self::$builder->elements());
        $this->assertNull(self::$builder->getCreatedAt());
        $this->assertNull(self::$builder->getPublishedAt());
        $this->assertNull(self::$builder->getArchiving());
        $this->assertNull(self::$builder->getState());
        $this->assertFalse(self::$builder->willBeSection());
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::getPage
     * @expectedException \Exception
     */
    public function testGetPageWithoutSite()
    {
        self::$builder
                ->setLayout($this->layout)
                ->setTitle('page-test');
        self::$builder->getPage();
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::getPage
     * @expectedException \Exception
     */
    public function testGetPageWithoutLayout()
    {
        self::$builder
                ->setSite($this->site)
                ->setTitle('page-test');
        self::$builder->getPage();
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::getPage
     * @expectedException \Exception
     */
    public function testGetPageWithoutTitle()
    {
        self::$builder
                ->setSite($this->site)
                ->setLayout($this->layout);
        self::$builder->getPage();
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::getPage
     */
    public function testGetPageNoPersist()
    {
        $yesterday = new \DateTime('yesterday');
        $tomorrow = new \DateTime('tomorrow');
        $now = new \DateTime();

        $page = self::$builder
                ->setUid('uid')
                ->setTitle('title')
                ->setAltTitle('alttitle')
                ->setRedirect('redirect')
                ->setTarget('target')
                ->setUrl('url')
                ->setSite($this->site)
                ->setRoot($this->root)
                ->setParent($this->root)
                ->setLayout($this->layout)
                ->pushElement(new MockContent(), true, 0)
                ->createdAt($yesterday)
                ->publishedAt($now)
                ->setArchiving($tomorrow)
                ->setState(Page::STATE_ONLINE)
                ->isSection(true)
                ->setPersistMode(PageBuilder::NO_PERSIST)
                ->getPage();

        $this->assertInstanceOf('BackBee\NestedNode\Page', $page);
        $this->assertEquals('uid', $page->getUid());
        $this->assertEquals('title', $page->getTitle());
        $this->assertEquals('alttitle', $page->getAltTitle());
        $this->assertEquals('redirect', $page->getRedirect());
        $this->assertEquals('target', $page->getTarget());
        $this->assertEquals('url', $page->getUrl(false));
        $this->assertEquals($this->site, $page->getSite());
        $this->assertEquals($this->root, $page->getRoot());
        $this->assertEquals($this->root, $page->getParent());
        $this->assertEquals($this->layout, $page->getLayout());
        $this->assertEquals($yesterday, $page->getCreated());
        $this->assertEquals($now, $page->getPublishing());
        $this->assertEquals($tomorrow, $page->getArchiving());
        $this->assertEquals(Page::STATE_ONLINE, $page->getState());
        $this->assertFalse($page->isRoot());

        // $page gets a main section only with persist
        $this->assertFalse($page->hasMainSection());

        // Test that main contentset and columns are "validated" - according to default layout
        $this->assertEquals(1, $page->getContentSet()->getRevision());
        $this->assertEquals(1, $page->getContentSet()->first()->getRevision());
        $this->assertEquals(1, $page->getContentSet()->last()->getRevision());
        $this->assertEquals(AbstractClassContent::STATE_NORMAL, $page->getContentSet()->getState());
        $this->assertEquals(AbstractClassContent::STATE_NORMAL, $page->getContentSet()->first()->getState());
        $this->assertEquals(AbstractClassContent::STATE_NORMAL, $page->getContentSet()->last()->getState());

        // Test that second column is inherited - according to default layout
        $this->assertEquals($this->root->getContentSet()->last(), $page->getContentSet()->last());

        // Test that first column get a ContentSet by default - according to default layout
        $this->assertInstanceOf('BackBee\ClassContent\ContentSet', $page->getContentSet()->first()->first());

        // Test that push content has main node set
        $content = $page->getContentSet()->first()->last();
        $this->assertInstanceOf('BackBee\ClassContent\Tests\Mock\MockContent', $content);
        $this->assertEquals($page, $content->getMainNode());

        // After getting the page, the builder is reset
        $this->testReset();
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::getPage
     * @covers \BackBee\NestedNode\Builder\PageBuilder::doPersistIfValid
     */
    public function testGetRootPagePersisted()
    {
        $page = self::$builder
                ->setUid('new-root')
                ->setTitle('title')
                ->setSite($this->site)
                ->setLayout($this->layout)
                ->setPersistMode(PageBuilder::PERSIST_AS_FIRST_CHILD)
                ->getPage();

        $this->assertTrue($page->isRoot());
        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForInsert($page));

        // Flush do not raise any error
        self::$em->flush();
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::getPage
     * @covers \BackBee\NestedNode\Builder\PageBuilder::doPersistIfValid
     */
    public function testGetPagePersisted()
    {
        $chid1 = self::$builder
                ->setUid('first-child')
                ->setTitle('title')
                ->setSite($this->site)
                ->setLayout($this->layout)
                ->setParent($this->root)
                ->setPersistMode(PageBuilder::PERSIST_AS_FIRST_CHILD)
                ->getPage();

        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForInsert($chid1));

        self::$em->flush();
        self::$em->refresh($this->root);

        $this->assertFalse($chid1->isRoot());
        $this->assertFalse($chid1->hasMainSection());
        $this->assertEquals(1, $this->root->getLeftnode());
        $this->assertEquals(2, $this->root->getRightnode());
        $this->assertEquals(1, $chid1->getPosition());
        $this->assertEquals(1, $chid1->getLevel());

        $child2 = self::$builder
                ->setUid('second-child')
                ->setTitle('title')
                ->setSite($this->site)
                ->setLayout($this->layout)
                ->setParent($this->root)
                ->setPersistMode(PageBuilder::PERSIST_AS_LAST_CHILD)
                ->getPage();

        self::$em->flush();
        $this->assertEquals(2, $child2->getPosition());
        $this->assertEquals(1, $child2->getLevel());

        $section = self::$builder
                ->setUid('section')
                ->setTitle('title')
                ->setSite($this->site)
                ->setLayout($this->layout)
                ->setParent($this->root)
                ->isSection(true)
                ->setPersistMode(PageBuilder::PERSIST_AS_FIRST_CHILD)
                ->getPage();

        self::$em->flush();
        self::$em->refresh($this->root);

        $this->assertTrue($section->hasMainSection());
        $this->assertEquals(0, $section->getPosition());
        $this->assertEquals(1, $section->getLevel());
        $this->assertEquals(2, $section->getLeftnode());
        $this->assertEquals(3, $section->getRightnode());
        $this->assertEquals(1, $this->root->getLeftnode());
        $this->assertEquals(4, $this->root->getRightnode());
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::setUrl
     */
    public function testSetUrl()
    {
        self::$builder->setUrl('test//with//duplicated////slashes');

        $this->assertEquals('test/with/duplicated/slashes', self::$builder->getUrl());
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::setRoot
     */
    public function testSetRoot()
    {
        self::$builder->setRoot($this->root);
        $this->assertNull(self::$builder->getParent());

        self::$builder->setRoot($this->root, true);
        $this->assertEquals($this->root, self::$builder->getParent());
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::addElement
     */
    public function testAddElement()
    {
        $content1 = new MockContent();
        $content2 = new MockContent();

        self::$builder->addElement($content1);
        $this->assertEquals(
                [
                    'content'               => $content1,
                    'set_main_node'         => false,
                    'content_set_position'  => 0,
                ],
                self::$builder->getElement(0)
        );
        self::$builder->addElement($content2, 0, true, 1);
        $this->assertEquals(
                [
                    'content'               => $content2,
                    'set_main_node'         => true,
                    'content_set_position'  => 1,
                ],
                self::$builder->getElement(0)
        );
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::addElement
     * @expectedException \InvalidArgumentException
     */
    public function testAddElementOnUnknownIndex()
    {
        self::$builder->addElement(new MockContent(), 2);
    }

    /**
     * @covers \BackBee\NestedNode\Builder\PageBuilder::getElement
     */
    public function testGetElement()
    {
        self::$builder->pushElement(new MockContent());
        $this->assertNotNull(self::$builder->getElement(0));
        $this->assertNull(self::$builder->getElement(2));
    }
}
