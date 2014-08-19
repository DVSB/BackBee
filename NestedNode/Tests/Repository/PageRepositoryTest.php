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

namespace BackBuilder\NestedNode\Tests\Repository;

use BackBuilder\Tests\TestCase,
    BackBuilder\Site\Site,
    BackBuilder\Site\Layout,
    BackBuilder\NestedNode\Page,
    BackBuilder\NestedNode\Tests\Mock\MockNestedNode,
    BackBuilder\NestedNode\Repository\PageRepository,
    BackBuilder\NestedNode\Repository\PageQueryBuilder,
    BackBuilder\ClassContent\ContentSet;

/**
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Tests\Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageRepositoryTest extends TestCase
{

    /**
     * @var \BackBuilder\Tests\Mock\MockBBApplication
     */
    private $application;

    /**
     * @var \BackBuilder\NestedNode\Page
     */
    private $root;

    /**
     * @var \BackBuilder\NestedNode\Repository\PageRepository
     */
    private $repo;

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::createQueryBuilder
     */
    public function testCreateQueryBuilder()
    {
        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageRepository', $this->repo);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOnlineDescendants
     */
    public function testGetOnlineDescendants()
    {
        $this->assertEquals(array(), $this->repo->getOnlineDescendants($this->root));

        $child1 = $this->repo->find('child1');
        $child1->setState(Page::STATE_ONLINE);
        $child2 = $this->repo->find('child2');
        $child2->setState(Page::STATE_ONLINE);
        $this->application->getEntityManager()->flush();

        $this->assertEquals(array($child2, $child1), $this->repo->getOnlineDescendants($this->root));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOnlinePrevSibling
     */
    public function testGetOnlinePrevSibling()
    {
        $child1 = $this->repo->find('child1');
        $this->assertNull($this->repo->getOnlinePrevSibling($child1));

        $child2 = $this->repo->find('child2');
        $child2->setState(Page::STATE_ONLINE);
        $this->application->getEntityManager()->flush($child2);

        $this->assertEquals($child2, $this->repo->getOnlinePrevSibling($child1));
        $this->assertNull($this->repo->getOnlinePrevSibling($child2));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOnlineSiblings
     */
    public function testGetOnlineSiblings()
    {
        $child1 = $this->repo->find('child1');
        $child2 = $this->repo->find('child2');
        $child3 = $this->repo->find('child3');

        $this->assertEquals(array(), $this->repo->getOnlineSiblings($child2));

        $child1->setState(Page::STATE_ONLINE);
        $child3->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
        $this->application->getEntityManager()->flush();

        $this->assertEquals(array($child3, $child1), $this->repo->getOnlineSiblings($child2));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOnlineSiblingsByLayout
     */
    public function testGetOnlineSiblingsByLayout()
    {
        $child1 = $this->repo->find('child1');
        $child2 = $this->repo->find('child2');
        $child3 = $this->repo->find('child3');

        $this->assertEquals(array(), $this->repo->getOnlineSiblingsByLayout($child2, $this->root->getLayout()));

        $child1->setLayout($this->root->getLayout())->setState(Page::STATE_ONLINE);
        $child3->setLayout($this->root->getLayout())->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
        $this->application->getEntityManager()->flush();

        $this->assertEquals(array($child3, $child1), $this->repo->getOnlineSiblingsByLayout($child2, $this->root->getLayout()));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOnlineNextSibling
     */
    public function testGetOnlineNextSibling()
    {
        $child2 = $this->repo->find('child2');
        $this->assertNull($this->repo->getOnlineNextSibling($child2));

        $child1 = $this->repo->find('child1');
        $child1->setState(Page::STATE_ONLINE);
        $this->application->getEntityManager()->flush($child1);

        $this->assertEquals($child1, $this->repo->getOnlineNextSibling($child2));
        $this->assertNull($this->repo->getOnlineNextSibling($child1));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::insertNodeAsFirstChildOf
     * @expectedException BackBuilder\Exception\InvalidArgumentException
     */
    public function testInsertNodeAsFirstChildOfWithWrongNode()
    {
        $mock = new MockNestedNode('fake');
        $this->repo->insertNodeAsFirstChildOf($mock, $this->root);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::insertNodeAsFirstChildOf
     * @expectedException BackBuilder\Exception\InvalidArgumentException
     */
    public function testInsertNodeAsFirstChildOfWithWrongParent()
    {
        $mock = new MockNestedNode('fake');
        $this->repo->insertNodeAsFirstChildOf(new Page('test'), $mock);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::insertNodeAsFirstChildOf
     */
    public function testInsertNodeAsFirstChildOf()
    {
        $new_child = new Page('new-child', array('title' => 'new-child', 'url' => 'url-new-child'));
        $this->repo->insertNodeAsFirstChildOf($new_child, $this->root);
        $this->assertEquals($this->root->getSite(), $new_child->getSite());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::insertNodeAsLastChildOf
     * @expectedException BackBuilder\Exception\InvalidArgumentException
     */
    public function testInsertNodeAsLastChildOfWithWrongNode()
    {
        $mock = new \BackBuilder\NestedNode\Tests\Mock\MockNestedNode('fake');
        $this->repo->insertNodeAsLastChildOf($mock, $this->root);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::insertNodeAsLastChildOf
     * @expectedException BackBuilder\Exception\InvalidArgumentException
     */
    public function testInsertNodeAsLastChildOfWithWrongParent()
    {
        $mock = new \BackBuilder\NestedNode\Tests\Mock\MockNestedNode('fake');
        $this->repo->insertNodeAsLastChildOf(new Page('test'), $mock);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::insertNodeAsLastChildOf
     */
    public function testInsertNodeAsLastChildOf()
    {
        $new_child = new Page('new-child', array('title' => 'new-child', 'url' => 'url-new-child'));
        $this->repo->insertNodeAsLastChildOf($new_child, $this->root);
        $this->assertEquals($this->root->getSite(), $new_child->getSite());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getVisibleDescendants
     */
    public function testGetVisibleDescendants()
    {
        $child1 = $this->repo->find('child1');
        $child3 = $this->repo->find('child3');

        $this->assertEquals(array(), $this->repo->getVisibleDescendants($this->root));

        $new_child = new Page('new-child', array('title' => 'new-child', 'url' => 'url-new-child'));
        $this->repo->insertNodeAsLastChildOf($new_child, $child3);

        $child1->setState(Page::STATE_ONLINE);
        $child3->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
        $new_child->setState(Page::STATE_ONLINE);

        $this->application->getEntityManager()->flush();
        $this->application->getEntityManager()->refresh($child1);
        $this->application->getEntityManager()->refresh($this->root);

        $this->assertEquals(array($new_child, $child1), $this->repo->getVisibleDescendants($this->root));
        $this->assertEquals(array($child1), $this->repo->getVisibleDescendants($this->root, 1));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getVisibleSiblings
     */
    public function testGetVisibleSiblings()
    {
        $child1 = $this->repo->find('child1');
        $child2 = $this->repo->find('child2');
        $child3 = $this->repo->find('child3');

        $this->assertEquals(array(), $this->repo->getVisibleSiblings($child2));

        $child1->setState(Page::STATE_ONLINE);
        $child3->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
        $this->application->getEntityManager()->flush();

        $this->assertEquals(array($child1), $this->repo->getVisibleSiblings($child2));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getVisiblePrevSibling
     */
    public function testGetVisiblePrevSibling()
    {
        $child1 = $this->repo->find('child1');
        $child2 = $this->repo->find('child2');

        $this->assertNull($this->repo->getVisiblePrevSibling($child1));

        $child2->setState(Page::STATE_ONLINE);
        $this->application->getEntityManager()->flush();

        $this->assertEquals($child2, $this->repo->getVisiblePrevSibling($child1));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::movePageInTree
     */
    public function testMovePageInTree()
    {
        $child1 = $this->repo->find('child1');
        $child2 = $this->repo->find('child2');
        $child3 = $this->repo->find('child3');

        $this->assertEquals($child3, $this->repo->movePageInTree($child3, $this->root));
        $this->assertEquals(6, $child3->getLeftnode());

        $this->assertEquals($child1, $this->repo->movePageInTree($child1, $this->root, $child2->getUid()));
        $this->assertEquals(2, $child1->getLeftnode());

        $this->assertEquals($child2, $this->repo->movePageInTree($child2, $this->root, $this->root->getUid()));
        $this->assertEquals(6, $child2->getLeftnode());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::replaceRootContentSet
     */
    public function testReplaceRootContentSet()
    {
        $newContentSet = new ContentSet();
        $this->assertEquals($newContentSet, $this->repo->replaceRootContentSet($this->root, $this->root->getContentSet()->last(), $newContentSet));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getVisibleNextSibling
     */
    public function testGetVisibleNextSibling()
    {
        $child2 = $this->repo->find('child2');
        $child3 = $this->repo->find('child3');

        $this->assertNull($this->repo->getVisibleNextSibling($child3));

        $child2->setState(Page::STATE_ONLINE);
        $this->application->getEntityManager()->flush();

        $this->assertEquals($child2, $this->repo->getVisibleNextSibling($child3));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getNotDeletedDescendants
     */
    public function testgetNotDeletedDescendants()
    {
        $child1 = $this->repo->find('child1');
        $child2 = $this->repo->find('child2');
        $child3 = $this->repo->find('child3');
        
        $this->assertEquals(array($child3, $child2, $child1), $this->repo->getNotDeletedDescendants($this->root));
        $this->assertEquals(array(), $this->repo->getNotDeletedDescendants($this->root), 0);
    }

    /**
     * Sets up the fixture
     */
    public function setUp()
    {
        $this->initAutoload();
        $this->application = $this->getBBApp();
        $this->initDb($this->application);

        $this->initAcl();
        $this->application->start();

        $site = new Site('site-test', array('label' => 'site-test'));
        $this->getEntityManager()->persist($site);

        $layout = new Layout('layout-test', array('label' => 'layout-test', 'path' => 'layout-path'));
        $layout->setDataObject($this->getDefaultLayoutZones());
        $this->getEntityManager()->persist($layout);

        $this->root = new Page('root', array('title' => 'root', 'url' => 'url-root'));
        $this->root->setSite($site)
                ->setLayout($layout);

        $this->getEntityManager()->persist($this->root);
        $this->getEntityManager()->flush();

        $this->_setRepo();

        $child1 = $this->repo->insertNodeAsFirstChildOf(new Page('child1', array('title' => 'child1', 'url' => 'url-child1')), $this->root);
        $this->getEntityManager()->flush($child1);

        $child2 = $this->repo->insertNodeAsFirstChildOf(new Page('child2', array('title' => 'child2', 'url' => 'url-child2')), $this->root);
        $this->getEntityManager()->flush($child2);

        $child3 = $this->repo->insertNodeAsFirstChildOf(new Page('child3', array('title' => 'child3', 'url' => 'url-child3')), $this->root);
        $this->getEntityManager()->flush($child3);

        $this->getEntityManager()->refresh($this->root);
        $this->getEntityManager()->refresh($child1);
        $this->getEntityManager()->refresh($child2);
    }

    /**
     * Sets the NestedNode Repository
     * @return \BackBuilder\NestedNode\Tests\Repository\NestedNodeRepositoryTest
     */
    private function _setRepo()
    {
        $this->repo = $this->getEntityManager()
                ->getRepository('BackBuilder\NestedNode\Page');

        PageRepository::$config = array(
            'nestedNodeCalculateAsync' => false
        );

        PageQueryBuilder::$config = array(
            'dateSchemeForPublishing' => 'Y-m-d H:i:s'
        );

        return $this;
    }

    /**
     * Builds a default set of layout zones
     * @return \stdClass
     */
    private function getDefaultLayoutZones()
    {
        $mainzone = new \stdClass();
        $mainzone->id = 'main';
        $mainzone->defaultContainer = null;
        $mainzone->target = '#target';
        $mainzone->gridClassPrefix = 'row';
        $mainzone->gridSize = 8;
        $mainzone->mainZone = true;
        $mainzone->defaultClassContent = 'ContentSet';
        $mainzone->options = null;

        $asidezone = new \stdClass();
        $asidezone->id = 'aside';
        $asidezone->defaultContainer = null;
        $asidezone->target = '#target';
        $asidezone->gridClassPrefix = 'row';
        $asidezone->gridSize = 4;
        $asidezone->mainZone = false;
        $asidezone->defaultClassContent = 'inherited';
        $asidezone->options = null;

        $data = new \stdClass();
        $data->templateLayouts = array(
            $mainzone,
            $asidezone
        );

        return $data;
    }

}
