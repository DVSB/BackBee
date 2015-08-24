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

use BackBee\NestedNode\Page;
use BackBee\NestedNode\Repository\PageQueryBuilder;
use BackBee\Site\Layout;
use BackBee\Site\Site;
use BackBee\Tests\BackBeeTestCase;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageRepositoryTest extends BackBeeTestCase
{

    private static $previousDateFormat;

    /**
     * @var \BackBee\NestedNode\Page
     */
    private $root;

    /**
     * @var \BackBee\NestedNode\Repository\PageRepository
     */
    private $repository;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->repository = self::$em->getRepository('BackBee\NestedNode\Page');
    }

    public static function setUpBeforeClass()
    {
        self::$kernel->resetDatabase(null, true);

        $site = new Site('site-test', ['label' => 'site-test']);
        $layout = self::$kernel->createLayout('layout-test', 'layout-test');
        self::$em->persist($site);
        self::$em->persist($layout);
        self::$em->flush();

        self::$previousDateFormat = PageQueryBuilder::$config['dateSchemeForPublishing'];
        PageQueryBuilder::$config = ['dateSchemeForPublishing' => 'Y-m-d H:i:s'];
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        self::$em->clear();

        self::$kernel->resetDatabase([
            self::$em->getClassMetaData('BackBee\ClassContent\AbstractClassContent'),
            self::$em->getClassMetaData('BackBee\ClassContent\Indexes\IdxContentContent'),
            self::$em->getClassMetaData('BackBee\ClassContent\Indexes\IdxSiteContent'),
            self::$em->getClassMetaData('BackBee\ClassContent\Indexes\OptContentByModified'),
            self::$em->getClassMetaData('BackBee\NestedNode\Page'),
            self::$em->getClassMetaData('BackBee\NestedNode\Section'),
        ]);

        $site = self::$em->find('BackBee\Site\Site', 'site-test');
        $layout = self::$em->find('BackBee\Site\Layout', 'layout-test');

        $this->root = new Page('root', ['title' => 'root']);
        $this->root->setSite($site)
                ->setLayout($layout);

        self::$em->persist($this->root);
        self::$em->flush();

        /**
         * Create mock tree:
         * root
         *  |- section2
         *  |- section1
         *  |      |- page2
         *  |      |- page1
         *  |- page3
         */
        $section1 = $this->addPage('section1', $layout, $this->root, true);
        $this->addPage('section2', $layout, $this->root, true);
        $this->addPage('page1', $layout, $section1);
        $this->addPage('page2', $layout, $section1);
        $this->addPage('page3', $layout, $this->root);
        self::$em->refresh($this->root);
    }

    /**
     * Add a new page in mock tree
     * @param  string                   $uid
     * @param  \BackBee\Site\Layout     $layout
     * @param  \BackBee\NestedNode\Page $parent
     * @param  boolean                  $section If TRUE, the page is inserted with a section
     * @return \BackBee\NestedNode\Page
     */
    private function addPage($uid, Layout $layout, Page $parent, $section = false)
    {
        $page = new Page($uid, array('title' => $uid));
        $page->setLayout($layout);
        $this->repository->insertNodeAsFirstChildOf($page, $parent, $section);
        self::$em->persist($page);
        self::$em->flush($page);

        foreach ($this->repository->findAll() as $node) {
            self::$em->refresh($node);
        }

        return $page;
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getAncestor
     */
    public function testSectionHasChildren()
    {
        $section1 = $this->repository->find('section1')->getSection();
        $root = $this->repository->find('root')->getSection();
        $page2 = $this->repository->find('page2');
        $page1 = $this->repository->find('page1');

        $this->assertTrue($root->getHasChildren(), 'Root has_children after loading');
        $this->assertTrue($section1->getHasChildren(), 'Section has_children after loading');
        $page1->setState(4);
        self::$em->persist($page1);
        self::$em->flush($page1);
        self::$em->refresh($section1);
        $this->assertTrue($section1->getHasChildren(), 'Section has_children after set page1 offline');

        $page2->setState(4);
        self::$em->persist($page2);
        self::$em->flush($page2);
        self::$em->refresh($section1);
        $this->assertFalse($section1->getHasChildren(), 'Section has_children after set page2 offline');

        $layout = self::$em->find('BackBee\Site\Layout', 'layout-test');
        $page4 = $this->addPage('page4', $layout, $section1->getPage());
        self::$em->refresh($section1);
        $this->assertTrue($section1->getHasChildren(), 'Section has_children after a page creation');

        $section2 = $this->repository->find('section2');
        $this->repository->moveAsLastChildOf($page4, $section2);
        self::$em->persist($page4);
        self::$em->flush($page4);
        self::$em->refresh($section1);
        self::$em->refresh($section2);
        $this->assertFalse($section1->getHasChildren(), 'Section has_children after set page2 offline');
        $this->assertTrue($section2->getSection()->getHasChildren(), 'Section has_children after a page creation');
    }

    public function testHardDeletePage()
    {
        $page1 = $this->repository->find('page1');
        $section1 = $this->repository->find('section1');
        $sectionRepo = self::$em->getRepository('BackBee\NestedNode\Section');

        $baseQuery = 'select uid from %s where uid = "%s"';

        $this->repository->deletePage($page1);
        self::$em->flush();

        $this->assertCount(5, $this->repository->findAll());
        $this->assertFalse(self::$em->getConnection()->executeQuery(sprintf($baseQuery, 'page', 'page1'))->fetch(), 'Page 1 isn\'t deleted');
        $this->repository->deletePage($section1);
        self::$em->flush();

        $this->assertCount(3, $this->repository->findAll());
        $this->assertFalse(self::$em->getConnection()->executeQuery(sprintf($baseQuery, 'page', 'section1'))->fetch(), 'the page of Section 1 isn\'t deleted');
        $this->assertFalse(self::$em->getConnection()->executeQuery(sprintf($baseQuery, 'section', 'section1'))->fetch(), 'the section of Section 1 isn\'t deleted');
        $this->assertFalse(self::$em->getConnection()->executeQuery(sprintf($baseQuery, 'page', 'page2'))->fetch(), 'the sub page 2 isn\'t deleted');

    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::findBy
     */
    public function testFindBy()
    {
        $this->assertEquals(array($this->root), $this->repository->findBy(array('_title' => 'root')));
        $this->assertEquals(6, count($this->repository->findBy(array('_site' => $this->root->getSite()))));
        $this->assertEquals(array($this->root), $this->repository->findBy(array('_title' => 'root'), array('_leftnode' => 'ASC'), 1, 0));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::findOneBy
     */
    public function testFindOneBy()
    {
        $this->assertEquals($this->root, $this->repository->findOneBy(array('_title' => 'root')));
        $this->assertEquals($this->root, $this->repository->findOneBy(array('_title' => 'root'), array('_leftnode' => 'ASC')));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getAncestor
     */
    public function testGetAncestor()
    {
        $page1 = $this->repository->find('page1');
        $section1 = $this->repository->find('section1');

        $this->assertEquals($this->root, $this->repository->getAncestor($page1));
        $this->assertEquals($this->root, $this->repository->getAncestor($page1, 0));
        $this->assertEquals($section1, $this->repository->getAncestor($page1, 1));
        $this->assertEquals($page1, $this->repository->getAncestor($page1, 2));
        $this->assertNull($this->repository->getAncestor($page1, 3));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getAncestors
     */
    public function testGetAncestors()
    {
        $page1 = $this->repository->find('page1');
        $section1 = $this->repository->find('section1');

        $this->assertEquals(array($this->root, $section1), $this->repository->getAncestors($page1));
        $this->assertEquals(array($section1), $this->repository->getAncestors($page1, 1));
        $this->assertEquals(array($section1, $page1), $this->repository->getAncestors($page1, 1, true));
        $this->assertEquals(array(), $this->repository->getAncestors($page1, 3));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOnlinePrevSibling
     */
    public function testGetOnlinePrevSibling()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');
        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        $page3 = $this->repository->find('page3');

        $this->assertNull($this->repository->getOnlinePrevSibling($section1));
        $this->assertNull($this->repository->getOnlinePrevSibling($section2));
        $this->assertNull($this->repository->getOnlinePrevSibling($page1));
        $this->assertNull($this->repository->getOnlinePrevSibling($page2));
        $this->assertNull($this->repository->getOnlinePrevSibling($page3));

        $section2->setState(Page::STATE_ONLINE);
        $section1->setState(Page::STATE_ONLINE);
        $page1->setState(Page::STATE_ONLINE);
        $page2->setState(Page::STATE_ONLINE);
        $page3->setState(Page::STATE_ONLINE);
        self::$em->flush();

        $this->assertEquals($section2, $this->repository->getOnlinePrevSibling($section1));
        $this->assertNull($this->repository->getOnlinePrevSibling($section2));
        $this->assertEquals($page2, $this->repository->getOnlinePrevSibling($page1));
        $this->assertNull($this->repository->getOnlinePrevSibling($page2));
        $this->assertEquals($section1, $this->repository->getOnlinePrevSibling($page3));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOnlineSiblingsByLayout
     */
    public function testGetOnlineSiblingsByLayout()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');
        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        $page3 = $this->repository->find('page3');

        $this->assertEquals(array(), $this->repository->getOnlineSiblingsByLayout($section1, $this->root->getLayout()));
        $this->assertEquals(array(), $this->repository->getOnlineSiblingsByLayout($section2, $this->root->getLayout()));
        $this->assertEquals(array(), $this->repository->getOnlineSiblingsByLayout($page1, $this->root->getLayout()));
        $this->assertEquals(array(), $this->repository->getOnlineSiblingsByLayout($page2, $this->root->getLayout()));
        $this->assertEquals(array(), $this->repository->getOnlineSiblingsByLayout($page3, $this->root->getLayout()));

        $section2->setState(Page::STATE_ONLINE);
        $section1->setState(Page::STATE_ONLINE);
        $page1->setState(Page::STATE_ONLINE);
        $page2->setState(Page::STATE_ONLINE);
        $page3->setState(Page::STATE_ONLINE);
        self::$em->flush();

        $this->assertEquals(array($section2, $page3), $this->repository->getOnlineSiblingsByLayout($section1, $this->root->getLayout(), false, array('_position' => 'ASC', '_leftnode' => 'ASC')));
        $this->assertEquals(array($section1, $page3), $this->repository->getOnlineSiblingsByLayout($section2, $this->root->getLayout(), false, array('_position' => 'ASC', '_leftnode' => 'ASC')));
        $this->assertEquals(array($page2), $this->repository->getOnlineSiblingsByLayout($page1, $this->root->getLayout(), false, array('_position' => 'ASC', '_leftnode' => 'ASC')));
        $this->assertEquals(array($page1), $this->repository->getOnlineSiblingsByLayout($page2, $this->root->getLayout(), false, array('_position' => 'ASC', '_leftnode' => 'ASC')));
        $this->assertEquals(array($section2, $section1), $this->repository->getOnlineSiblingsByLayout($page3, $this->root->getLayout(), false, array('_position' => 'ASC', '_leftnode' => 'ASC')));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOnlineNextSibling
     */
    public function testGetOnlineNextSibling()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');
        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        $page3 = $this->repository->find('page3');

        $this->assertNull($this->repository->getOnlineNextSibling($section1));
        $this->assertNull($this->repository->getOnlineNextSibling($section2));
        $this->assertNull($this->repository->getOnlineNextSibling($page1));
        $this->assertNull($this->repository->getOnlineNextSibling($page2));
        $this->assertNull($this->repository->getOnlineNextSibling($page3));

        $section2->setState(Page::STATE_ONLINE);
        $section1->setState(Page::STATE_ONLINE);
        $page1->setState(Page::STATE_ONLINE);
        $page2->setState(Page::STATE_ONLINE);
        $page3->setState(Page::STATE_ONLINE);
        self::$em->flush();

        $this->assertEquals($page3, $this->repository->getOnlineNextSibling($section1));
        $this->assertEquals($section1, $this->repository->getOnlineNextSibling($section2));
        $this->assertNull($this->repository->getOnlineNextSibling($page1));
        $this->assertEquals($page1, $this->repository->getOnlineNextSibling($page2));
        $this->assertNull($this->repository->getOnlineNextSibling($page3));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::insertNodeAsFirstChildOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::insertNodeAsLastChildOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::insertNode
     * @covers \BackBee\NestedNode\Repository\PageRepository::getMaxPosition
     */
    public function testInsertNode()
    {
        $newpage1 = new Page('new-page1', array('title' => 'new-page1'));
        $newpage1->setLayout(new Layout());
        $this->repository->insertNodeAsFirstChildOf($newpage1, $this->root);

        foreach ($this->repository->findAll() as $node) {
            self::$em->refresh($node);
        }

        $page3 = $this->repository->find('page3');

        $this->assertFalse($newpage1->hasMainSection());
        $this->assertEquals($this->root, $newpage1->getParent());
        $this->assertEquals(1, $newpage1->getLevel());
        $this->assertEquals(1, $newpage1->getPosition());
        $this->assertEquals(6, $this->root->getRightnode());
        $this->assertEquals(2, $page3->getPosition());

        $section2 = $this->repository->find('section2');
        $newpage2 = new Page('new-page2', array('title' => 'new-page2'));
        $newpage2->setLayout(new Layout());
        $this->repository->insertNodeAsLastChildOf($newpage2, $section2, true);

        foreach ($this->repository->findAll() as $node) {
            self::$em->refresh($node);
        }

        $this->assertTrue($newpage2->hasMainSection());
        $this->assertEquals($section2, $newpage2->getParent());
        $this->assertEquals(2, $newpage2->getLevel());
        $this->assertEquals(0, $newpage2->getPosition());
        $this->assertEquals(3, $newpage2->getLeftnode());
        $this->assertEquals(4, $newpage2->getRightnode());
        $this->assertEquals(2, $section2->getLeftnode());
        $this->assertEquals(5, $section2->getRightnode());
        $this->assertEquals(8, $this->root->getRightnode());

        $section1 = $this->repository->find('section1');
        $newpage3 = new Page('new-page3', array('title' => 'new-page3'));
        $newpage3->setLayout(new Layout());

        $this->repository->insertNodeAsLastChildOf($newpage3, $section1, false);
        foreach ($this->repository->findAll() as $node) {
            self::$em->refresh($node);
        }

        $this->assertEquals(3, $newpage3->getPosition());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::insertNode
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testInsertNodeInNonSection()
    {
        $page1 = $this->repository->find('page1');
        $newpage = new Page('new-page', array('title' => 'new-page'));
        $newpage->setLayout(new Layout());
        $this->repository->insertNode($newpage, $page1, 1);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getDescendants

     * @covers \BackBee\NestedNode\Repository\PageRepository::getOrderingDescendants
     */
    public function testGetDescendants()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');
        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        $page3 = $this->repository->find('page3');

        $this->assertEquals(array(), $this->repository->getDescendants($page1));
        $this->assertEquals(array($page3, $section2, $section1, $page2, $page1), $this->repository->getDescendants($this->root));
        $this->assertEquals(array($section2, $section1, $page3), $this->repository->getDescendants($this->root, 1));
        $this->assertEquals(array($page3, $section2, $section1, $page2, $page1), $this->repository->getDescendants($this->root, 2));
        $this->assertEquals(array($this->root, $page3, $section2, $section1, $page2, $page1), $this->repository->getDescendants($this->root, 2, true));
        $this->assertEquals(array($this->root, $page3, $section2, $section1, $page2, $page1), $this->repository->getDescendants($this->root, 2, true, array()));
        $this->assertEquals(array($page1, $page2, $page3, $this->root, $section1, $section2), $this->repository->getDescendants($this->root, 2, true, array('_title' => 'ASC')));
        $this->assertEquals(array($this->root, $section1, $section2), $this->repository->getDescendants($this->root, 2, true, array('_title' => 'ASC'), false, null, null, true));

        $result = $this->repository->getDescendants($this->root, 2, true, array(), true, 1, 2);
        $this->assertInstanceOf('Doctrine\ORM\Tools\Pagination\Paginator', $result);
        $this->assertEquals(6, $result->count());
        $this->assertEquals(2, $result->getIterator()->count());
        $this->assertEquals(1, $result->getQuery()->getFirstResult());
        $this->assertEquals(2, $result->getQuery()->getMaxResults());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOnlineDescendants
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOrderingDescendants
     */
    public function testGetOnlineDescendants()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');
        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        $page3 = $this->repository->find('page3');

        $this->root->setState(Page::STATE_ONLINE);
        $section2->setState(Page::STATE_ONLINE);
        $section1->setState(Page::STATE_ONLINE);
        $page1->setState(Page::STATE_ONLINE);
        $page2->setState(Page::STATE_ONLINE);
        $page3->setState(Page::STATE_ONLINE);
        self::$em->flush();

        $this->assertEquals(array(), $this->repository->getOnlineDescendants($page1));
        $this->assertEquals(array($page3, $section2, $section1, $page2, $page1), $this->repository->getOnlineDescendants($this->root));
        $this->assertEquals(array($section2, $section1, $page3), $this->repository->getOnlineDescendants($this->root, 1));
        $this->assertEquals(array($page3, $section2, $section1, $page2, $page1), $this->repository->getOnlineDescendants($this->root, 2));
        $this->assertEquals(array($this->root, $page3, $section2, $section1, $page2, $page1), $this->repository->getOnlineDescendants($this->root, 2, true));
        $this->assertEquals(array($this->root, $page3, $section2, $section1, $page2, $page1), $this->repository->getOnlineDescendants($this->root, 2, true, array()));
        $this->assertEquals(array($page1, $page2, $page3, $this->root, $section1, $section2), $this->repository->getOnlineDescendants($this->root, 2, true, array('_title' => 'ASC')));
        $this->assertEquals(array($this->root, $section1, $section2), $this->repository->getOnlineDescendants($this->root, 2, true, array('_title' => 'ASC'), false, null, null, true));

        $result = $this->repository->getOnlineDescendants($this->root, 2, true, array(), true, 1, 2);
        $this->assertInstanceOf('Doctrine\ORM\Tools\Pagination\Paginator', $result);
        $this->assertEquals(6, $result->count());
        $this->assertEquals(2, $result->getIterator()->count());
        $this->assertEquals(1, $result->getQuery()->getFirstResult());
        $this->assertEquals(2, $result->getQuery()->getMaxResults());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getVisibleDescendants
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOrderingDescendants
     */
    public function testGetVisibleDescendants()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');
        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        $page3 = $this->repository->find('page3');

        $this->root->setState(Page::STATE_ONLINE);
        $section2->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
        $section1->setState(Page::STATE_ONLINE);
        $page1->setState(Page::STATE_ONLINE);
        $page2->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
        $page3->setState(Page::STATE_ONLINE);
        self::$em->flush();

        $this->assertEquals(array(), $this->repository->getVisibleDescendants($page1));
        $this->assertEquals(array($page3, $section1, $page1), $this->repository->getVisibleDescendants($this->root));
        $this->assertEquals(array($section1, $page3), $this->repository->getVisibleDescendants($this->root, 1));
        $this->assertEquals(array($page3, $section1, $page1), $this->repository->getVisibleDescendants($this->root, 2));
        $this->assertEquals(array($this->root, $page3, $section1, $page1), $this->repository->getVisibleDescendants($this->root, 2, true));
        $this->assertEquals(array($this->root, $page3, $section1, $page1), $this->repository->getVisibleDescendants($this->root, 2, true, array()));
        $this->assertEquals(array($page1, $page3, $this->root, $section1), $this->repository->getVisibleDescendants($this->root, 2, true, array('_title' => 'ASC')));
        $this->assertEquals(array($this->root, $section1), $this->repository->getVisibleDescendants($this->root, 2, true, array('_title' => 'ASC'), false, null, null, true));

        $result = $this->repository->getVisibleDescendants($this->root, 2, true, array(), true, 1, 2);

        $this->assertInstanceOf('Doctrine\ORM\Tools\Pagination\Paginator', $result);
        $this->assertEquals(4, $result->count());
        $this->assertEquals(2, $result->getIterator()->count());
        $this->assertEquals(1, $result->getQuery()->getFirstResult());
        $this->assertEquals(2, $result->getQuery()->getMaxResults());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getNotDeletedDescendants
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOrderingDescendants
     */
    public function testGetNotDeletedDescendants()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');

        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        $page3 = $this->repository->find('page3');

        $section2->setState(Page::STATE_DELETED);
        $page2->setState(Page::STATE_DELETED);
        self::$em->flush();

        $this->assertEquals(array(), $this->repository->getNotDeletedDescendants($page1));
        $this->assertEquals(array($page3, $section1, $page1), $this->repository->getNotDeletedDescendants($this->root));
        $this->assertEquals(array($section1, $page3), $this->repository->getNotDeletedDescendants($this->root, 1));
        $this->assertEquals(array($page3, $section1, $page1), $this->repository->getNotDeletedDescendants($this->root, 2));
        $this->assertEquals(array($this->root, $page3, $section1, $page1), $this->repository->getNotDeletedDescendants($this->root, 2, true));
        $this->assertEquals(array($this->root, $page3, $section1, $page1), $this->repository->getNotDeletedDescendants($this->root, 2, true, array()));
        $this->assertEquals(array($page1, $page3, $this->root, $section1), $this->repository->getNotDeletedDescendants($this->root, 2, true, array('_title' => 'ASC')));
        $this->assertEquals(array($this->root, $section1), $this->repository->getNotDeletedDescendants($this->root, 2, true, array('_title' => 'ASC'), false, null, null, true));

        $result = $this->repository->getNotDeletedDescendants($this->root, 2, true, array(), true, 1, 2);
        $this->assertInstanceOf('Doctrine\ORM\Tools\Pagination\Paginator', $result);
        $this->assertEquals(4, $result->count());
        $this->assertEquals(2, $result->getIterator()->count());
        $this->assertEquals(1, $result->getQuery()->getFirstResult());
        $this->assertEquals(2, $result->getQuery()->getMaxResults());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsFirstChildOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsChildOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::movePageAsChildOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveSectionAsChildOf
     */
    public function testMoveAsFirstChidOf()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');
        $page3 = $this->repository->find('page3');

        $this->assertEquals($page3, $this->repository->moveAsFirstChildOf($page3, $section1));
        $this->assertEquals($section1, $page3->getParent());
        $this->assertEquals(1, $page3->getPosition());
        $this->assertEquals(2, $page3->getLevel());
        self::$em->refresh($section1);
        self::$em->flush($page3);

        $this->assertEquals($section1, $this->repository->moveAsFirstChildOf($section1, $section2));
        self::$em->refresh($section2);
        self::$em->refresh($page3);

        $this->assertEquals($section2, $section1->getParent());
        $this->assertEquals(2, $section1->getLevel());
        $this->assertEquals(3, $section1->getLeftnode());
        $this->assertEquals(4, $section1->getRightnode());
        $this->assertEquals(2, $section2->getLeftnode());
        $this->assertEquals(5, $section2->getRightnode());
        $this->assertEquals(3, $page3->getLevel());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsFirstChildOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsChildOf
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testMoveAsFirstChidOfNonSection()
    {
        $section1 = $this->repository->find('section1');
        $page1 = $this->repository->find('page1');

        $this->repository->moveAsFirstChildOf($section1, $page1);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsLastChildOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsChildOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::movePageAsChildOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveSectionAsChildOf
     */
    public function testMoveAsLastChidOf()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');
        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');

        $this->assertEquals($page2, $this->repository->moveAsLastChildOf($page2, $section2));
        self::$em->refresh($section2);
        self::$em->refresh($page1);
        self::$em->flush($page2);

        $this->assertEquals($section2, $page2->getParent());
        $this->assertEquals(1, $page2->getPosition());
        $this->assertEquals(2, $page2->getLevel());
        $this->assertEquals(1, $page1->getPosition());

        $this->assertEquals($section2, $this->repository->moveAsFirstChildOf($section2, $section1));
        self::$em->refresh($section1);
        self::$em->refresh($page1);
        self::$em->refresh($page2);

        $this->assertEquals($section1, $section2->getParent());
        $this->assertEquals(2, $section2->getLevel());
        $this->assertEquals(3, $section2->getLeftnode());
        $this->assertEquals(4, $section2->getRightnode());
        $this->assertEquals(2, $section1->getLeftnode());
        $this->assertEquals(5, $section1->getRightnode());
        $this->assertEquals(3, $page2->getLevel());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsLastChildOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsChildOf
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testMoveAsLastChildOfNonSection()
    {
        $section1 = $this->repository->find('section1');
        $page1 = $this->repository->find('page1');

        $this->repository->moveAsLastChildOf($section1, $page1);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsPrevSiblingOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsSiblingOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::movePageAsSiblingOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveSectionAsSiblingOf
     */
    public function testMoveAsPrevSiblingOf()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');
        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        $page3 = $this->repository->find('page3');

        $this->assertEquals($page3, $this->repository->moveAsPrevSiblingOf($page3, $page1));
        self::$em->refresh($page1);
        self::$em->refresh($page2);
        self::$em->flush($page3);

        $this->assertEquals($section1, $page3->getParent());
        $this->assertEquals(2, $page3->getLevel());
        $this->assertEquals(1, $page2->getPosition());
        $this->assertEquals(2, $page3->getPosition());
        $this->assertEquals(3, $page1->getPosition());

        $this->assertEquals($section2, $this->repository->moveAsPrevSiblingOf($section2, $section1));
        self::$em->refresh($section1);
        self::$em->flush($section2);

        $this->assertEquals(1, $this->root->getLeftnode());
        $this->assertEquals(6, $this->root->getRightnode());
        $this->assertEquals(2, $section2->getLeftnode());
        $this->assertEquals(3, $section2->getRightnode());
        $this->assertEquals(4, $section1->getLeftnode());
        $this->assertEquals(5, $section1->getRightnode());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsNextSiblingOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsSiblingOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::movePageAsSiblingOf
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveSectionAsSiblingOf
     */
    public function testMoveAsNextSiblingOf()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');

        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        $page3 = $this->repository->find('page3');

        $this->assertEquals($page3, $this->repository->moveAsNextSiblingOf($page3, $page2));
        self::$em->refresh($page1);
        self::$em->refresh($page2);
        self::$em->flush($page3);

        $this->assertEquals($section1, $page3->getParent());
        $this->assertEquals(2, $page3->getLevel());
        $this->assertEquals(1, $page2->getPosition());
        $this->assertEquals(2, $page3->getPosition());
        $this->assertEquals(3, $page1->getPosition());

        $this->assertEquals($section1, $this->repository->moveAsNextSiblingOf($section1, $section2));
        self::$em->refresh($section2);
        self::$em->flush($section1);

        $this->assertEquals(1, $this->root->getLeftnode());
        $this->assertEquals(6, $this->root->getRightnode());
        $this->assertEquals(2, $section2->getLeftnode());
        $this->assertEquals(3, $section2->getRightnode());
        $this->assertEquals(4, $section1->getLeftnode());
        $this->assertEquals(5, $section1->getRightnode());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveAsSiblingOf
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testMoveAsSiblingOfRoot()
    {
        $section1 = $this->repository->find('section1');

        $this->repository->moveAsPrevSiblingOf($section1, $this->root);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::movePageAsSiblingOf
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testMoveNonSectionAsSiblingOfSection()
    {
        $section1 = $this->repository->find('section1');
        $page1 = $this->repository->find('page1');

        $this->repository->moveAsPrevSiblingOf($page1, $section1);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::moveSectionAsSiblingOf
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testMoveSectionAsSiblingOfNonSection()
    {
        $section1 = $this->repository->find('section1');
        $page1 = $this->repository->find('page1');

        $this->repository->moveAsPrevSiblingOf($section1, $page1);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getRoot
     */
    public function testGetRoot()
    {
        $this->assertEquals($this->root, $this->repository->getRoot($this->root->getSite()));
        $this->assertEquals($this->root, $this->repository->getRoot($this->root->getSite(), array(Page::STATE_HIDDEN)));
        $this->assertNull($this->repository->getRoot($this->root->getSite(), array(Page::STATE_ONLINE)));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::toTrash
     */
    public function testToTrash()
    {
        $section1 = $this->repository->find('section1');
        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        $page3 = $this->repository->find('page3');

        $this->assertEquals(1, $this->repository->toTrash($page3));
        $this->assertEquals(Page::STATE_DELETED, $page3->getState());

        $this->assertEquals(3, $this->repository->toTrash($section1));
        self::$em->refresh($section1);
        self::$em->refresh($page1);
        self::$em->refresh($page2);
        $this->assertEquals(Page::STATE_DELETED, $section1->getState());
        $this->assertEquals(Page::STATE_DELETED, $page1->getState());
        $this->assertEquals(Page::STATE_DELETED, $page2->getState());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::copy
     */
    public function testCopy()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');
        $page1 = $this->repository->find('page1');

        $new_page1 = $this->invokeMethod($this->repository, 'copy', array($page1));
        $this->assertInstanceOf('BackBee\NestedNode\Page', $new_page1);
        $this->assertTrue(self::$em->contains($new_page1));
        $this->assertFalse($new_page1->hasMainSection());
        $this->assertEquals('page1', $new_page1->getTitle());
        $this->assertEquals($page1->getParent(), $new_page1->getParent());

        $new_page2 = $this->invokeMethod($this->repository, 'copy', array($page1, 'new_title'));
        $this->assertInstanceOf('BackBee\NestedNode\Page', $new_page2);
        $this->assertEquals('new_title', $new_page2->getTitle());

        $new_page3 = $this->invokeMethod($this->repository, 'copy', array($page1, 'new_title', $section2));
        $this->assertInstanceOf('BackBee\NestedNode\Page', $new_page3);
        $this->assertEquals($section2, $new_page3->getParent());

        $new_section = $this->invokeMethod($this->repository, 'copy', array($section1, 'new_section', $this->root));
        $this->assertInstanceOf('BackBee\NestedNode\Page', $new_section);
        $this->assertTrue($new_section->hasMainSection());
        $this->assertEquals($this->root->getSection(), $new_section->getSection()->getParent());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::copy
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testCopyDeletedPage()
    {
        $page1 = $this->repository->find('page1');
        $page1->setState(Page::STATE_DELETED);

        $this->invokeMethod($this->repository, 'copy', array($page1));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::duplicate
     */
    public function testDuplicate()
    {
        // Duplicate a root not recursively to new a one
        $clone1 = $this->repository->duplicate($this->root, null, null, false);
        $this->assertInstanceOf('BackBee\NestedNode\Page', $clone1);
        $this->assertNull($clone1->getParent());
        $this->assertEquals('root', $clone1->getTitle());
        $this->assertTrue($clone1->hasMainSection());
        $this->assertEquals(1, $clone1->getLeftnode());
        $this->assertEquals(2, $clone1->getRightnode());
        $this->assertNotEquals($this->root->getContentSet(), $clone1->getContentSet());

        // Duplicate a root not recursively to one of its descendant
        $section1 = $this->repository->find('section1');
        $clone2 = $this->repository->duplicate($this->root, null, $section1, false);
        $this->assertInstanceOf('BackBee\NestedNode\Page', $clone2);
        $this->assertEquals($section1, $clone2->getParent());
        $this->assertTrue($clone2->hasMainSection());

        // Duplicate a section not recursively to another one
        $section2 = $this->repository->find('section2');
        $section3 = $this->repository->duplicate($section2, null, $section1, false);
        $this->assertInstanceOf('BackBee\NestedNode\Page', $section3);
        $this->assertEquals($section1, $section3->getParent());
        $this->assertTrue($section3->hasMainSection());

        // Duplicate a page in a section with a new title
        $page3 = $this->repository->find('page3');
        $page4 = $this->repository->duplicate($page3, 'page4', $section1, false);
        $this->assertInstanceOf('BackBee\NestedNode\Page', $page4);
        $this->assertEquals($section1, $page4->getParent());
        $this->assertFalse($page4->hasMainSection());
        $this->assertEquals('page4', $page4->getTitle());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::duplicateRecursively
     * @covers \BackBee\NestedNode\Repository\PageRepository::updateMainNodePostCloning
     * @covers \BackBee\NestedNode\Repository\PageRepository::updateRelatedPostCloning
     */
    public function testDuplicateRecursively()
    {
        // Initialize some mainnodes and circular related links
        $descendants = $this->repository->getDescendants($this->root, null, true);
        foreach ($descendants as $child) {
            $child->getContentSet()->first()->setMainNode($child);
        }
        $page1 = $this->repository->find('page1');
        $page3 = $this->repository->find('page3');
        $page1->getContentSet()->first()->push($page3->getContentSet()->first());
        $page3->getContentSet()->first()->push($page1->getContentSet()->first());
        self::$em->flush();

        // Recursively duplicate a tree
        $clone1 = $this->repository->duplicate($this->root);
        $this->assertInstanceOf('BackBee\NestedNode\Page', $clone1);
        $this->assertNull($clone1->getParent());
        $this->assertEquals('root', $clone1->getTitle());
        $this->assertTrue($clone1->hasMainSection());

        $new_descendants = $this->repository->getDescendants($clone1, null, true);
        $this->assertEquals(count($descendants), count($new_descendants));

        $new_page1 = $new_page3 = null;
        for ($i = 0; $i < count($descendants); $i++) {
            self::$em->refresh($new_descendants[$i]);
            $this->assertEquals($descendants[$i]->getTitle(), $new_descendants[$i]->getTitle());
            $this->assertEquals($descendants[$i]->hasMainSection(), $new_descendants[$i]->hasMainSection());
            $this->assertEquals($descendants[$i]->getLeftnode(), $new_descendants[$i]->getLeftnode());
            $this->assertEquals($descendants[$i]->getRightnode(), $new_descendants[$i]->getRightnode());
            $this->assertEquals($descendants[$i]->getPosition(), $new_descendants[$i]->getPosition());
            $this->assertEquals($descendants[$i]->getLevel(), $new_descendants[$i]->getLevel());
            $this->assertEquals($new_descendants[$i], $new_descendants[$i]->getContentSet()->first()->getMainNode());

            if ('page1' === $new_descendants[$i]->getTitle()) {
                $new_page1 = $new_descendants[$i];
            } elseif ('page3' === $new_descendants[$i]->getTitle()) {
                $new_page3 = $new_descendants[$i];
            }
        }
        $this->assertEquals($new_page1->getContentSet()->first()->last()->getUid(), $new_page3->getContentSet()->first()->getUid());
        $this->assertEquals($new_page3->getContentSet()->first()->last()->getUid(), $new_page1->getContentSet()->first()->getUid());

        // Duplicate children except deleted ones
        $section1 = $this->repository->find('section1');
        $section1->setState(Page::STATE_DELETED);
        $clone2 = $this->repository->duplicate($this->root);
        $this->assertEquals(count($this->repository->getDescendants($this->root)) - 3, count($this->repository->getDescendants($clone2)));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::duplicateRecursively
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testDuplicatePageIn0neOfItsDescendants()
    {
        $section1 = $this->repository->find('section1');

        $this->repository->duplicate($this->root, null, $section1);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::saveWithSection
     */
    public function testSaveWithSection()
    {
        $this->assertEquals($this->root, $this->repository->saveWithSection($this->root));

        $page2 = $this->repository->find('page2');
        $this->assertEquals($page2, $this->repository->saveWithSection($page2));
        self::$em->flush();
        self::$em->refresh($page2);

        $this->assertTrue($page2->hasMainSection());
        $this->assertEquals(0, $page2->getPosition());
        $this->assertEquals(2, $page2->getLevel());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::shiftPosition
     */
    public function testShiftPosition()
    {
        $this->assertEquals($this->repository, $this->invokeMethod($this->repository, 'shiftPosition', array($this->root, 1)));
        $this->assertEquals(0, $this->root->getPosition());

        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        self::$em->refresh($page1);
        self::$em->refresh($page2);

        $this->assertEquals($this->repository, $this->invokeMethod($this->repository, 'shiftPosition', array($page2, 1)));
        $this->assertEquals(2, $page2->getPosition());

        self::$em->refresh($page1);
        $this->assertEquals(3, $page1->getPosition());
        $this->assertEquals($this->repository, $this->invokeMethod($this->repository, 'shiftPosition', array($page2, 1, true)));
        $this->assertEquals(2, $page2->getPosition());

        self::$em->refresh($page1);
        $this->assertEquals(4, $page1->getPosition());
    }

    public function testShiftLevel()
    {
        $section1 = $this->repository->find('section1');
        $section2 = $this->repository->find('section2');
        $page1 = $this->repository->find('page1');
        $page2 = $this->repository->find('page2');
        $page3 = $this->repository->find('page3');

        $this->assertEquals($this->repository, $this->invokeMethod($this->repository, 'shiftLevel', array($page3, 1, true)));
        self::$em->refresh($page3);
        $this->assertEquals(1, $page3->getLevel());

        $this->assertEquals($this->repository, $this->invokeMethod($this->repository, 'shiftLevel', array($page3, 1, false)));
        self::$em->refresh($page3);
        $this->assertEquals(2, $page3->getLevel());

        $this->invokeMethod($this->repository, 'shiftLevel', array($this->root, 2));
        foreach ($this->repository->findAll() as $node) {
            self::$em->refresh($node);
        }
        $this->assertEquals(2, $this->root->getLevel());
        $this->assertEquals(3, $section1->getLevel());
        $this->assertEquals(3, $section2->getLevel());
        $this->assertEquals(4, $page1->getLevel());
        $this->assertEquals(4, $page2->getLevel());
        $this->assertEquals(4, $page3->getLevel());

        $this->invokeMethod($this->repository, 'shiftLevel', array($this->root, -2, true));
        foreach ($this->repository->findAll() as $node) {
            self::$em->refresh($node);
        }
        $this->assertEquals(2, $this->root->getLevel());
        $this->assertEquals(1, $section1->getLevel());
        $this->assertEquals(1, $section2->getLevel());
        $this->assertEquals(2, $page1->getLevel());
        $this->assertEquals(2, $page2->getLevel());
        $this->assertEquals(2, $page3->getLevel());
    }

    public static function tearDownAfterClass()
    {
        PageQueryBuilder::$config = array(
            'dateSchemeForPublishing' => self::$previousDateFormat,
        );
    }
}
