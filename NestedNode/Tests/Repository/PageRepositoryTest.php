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

use BackBee\ClassContent\ContentSet;
use BackBee\NestedNode\Page;
use BackBee\NestedNode\Repository\PageQueryBuilder;
use BackBee\NestedNode\Repository\PageRepository;
use BackBee\NestedNode\Tests\Mock\MockNestedNode;
use BackBee\Site\Layout;
use BackBee\Site\Site;
use BackBee\Tests\TestCase;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageRepositoryTest extends TestCase
{
    /**
     * @var \BackBee\Tests\Mock\MockBBApplication
     */
    private $application;

    /**
     * @var \BackBee\NestedNode\Page
     */
    private $root;

    /**
     * @var \BackBee\NestedNode\Repository\PageRepository
     */
    private $repo;

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::createQueryBuilder
     */
    public function testCreateQueryBuilder()
    {
        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageRepository', $this->repo);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOnlineDescendants
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
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOnlinePrevSibling
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
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOnlineSiblings
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
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOnlineSiblingsByLayout
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
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOnlineNextSibling
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
     * @covers \BackBee\NestedNode\Repository\PageRepository::insertNodeAsFirstChildOf
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testInsertNodeAsFirstChildOfWithWrongNode()
    {
        $mock = new MockNestedNode('fake');
        $this->repo->insertNodeAsFirstChildOf($mock, $this->root);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::insertNodeAsFirstChildOf
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testInsertNodeAsFirstChildOfWithWrongParent()
    {
        $mock = new MockNestedNode('fake');
        $this->repo->insertNodeAsFirstChildOf(new Page('test'), $mock);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::insertNodeAsFirstChildOf
     */
    public function testInsertNodeAsFirstChildOf()
    {
        $new_child = new Page('new-child', array('title' => 'new-child', 'url' => 'url-new-child'));
        $this->repo->insertNodeAsFirstChildOf($new_child, $this->root);
        $this->assertEquals($this->root->getSite(), $new_child->getSite());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::insertNodeAsLastChildOf
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testInsertNodeAsLastChildOfWithWrongNode()
    {
        $mock = new \BackBee\NestedNode\Tests\Mock\MockNestedNode('fake');
        $this->repo->insertNodeAsLastChildOf($mock, $this->root);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::insertNodeAsLastChildOf
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testInsertNodeAsLastChildOfWithWrongParent()
    {
        $mock = new \BackBee\NestedNode\Tests\Mock\MockNestedNode('fake');
        $this->repo->insertNodeAsLastChildOf(new Page('test'), $mock);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::insertNodeAsLastChildOf
     */
    public function testInsertNodeAsLastChildOf()
    {
        $new_child = new Page('new-child', array('title' => 'new-child', 'url' => 'url-new-child'));
        $this->repo->insertNodeAsLastChildOf($new_child, $this->root);
        $this->assertEquals($this->root->getSite(), $new_child->getSite());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getVisibleDescendants
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
     * @covers \BackBee\NestedNode\Repository\PageRepository::getVisibleSiblings
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
     * @covers \BackBee\NestedNode\Repository\PageRepository::getVisiblePrevSibling
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
     * @covers \BackBee\NestedNode\Repository\PageRepository::movePageInTree
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
     * @covers \BackBee\NestedNode\Repository\PageRepository::replaceRootContentSet
     */
    public function testReplaceRootContentSet()
    {
        $newContentSet = new ContentSet();
        $this->assertEquals($newContentSet, $this->repo->replaceRootContentSet($this->root, $this->root->getContentSet()->last(), $newContentSet));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getVisibleNextSibling
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
     * @covers \BackBee\NestedNode\Repository\PageRepository::getNotDeletedDescendants
     */
    public function testgetNotDeletedDescendants()
    {
        $child1 = $this->repo->find('child1');
        $child2 = $this->repo->find('child2');
        $child3 = $this->repo->find('child3');

        $this->assertEquals(array($child3, $child2, $child1), $this->repo->getNotDeletedDescendants($this->root));
        $this->assertEquals(array(), $this->repo->getNotDeletedDescendants($this->root, 0));
        $this->assertEquals(array($this->root, $child3, $child2, $child1), $this->repo->getNotDeletedDescendants($this->root, null, true));
        $this->assertEquals(array($child1, $child2, $child3), $this->repo->getNotDeletedDescendants($this->root, null, false, array('field' => '_title')));
        $this->assertEquals(array($child1, $child2, $child3), $this->repo->getNotDeletedDescendants($this->root, null, false, array('field' => 'title')));
        $this->assertEquals(array($child3, $child2, $child1), $this->repo->getNotDeletedDescendants($this->root, null, false, array('field' => '_title', 'sort' => 'desc')));
        $this->assertInstanceOf('Doctrine\ORM\Tools\Pagination\Paginator', $this->repo->getNotDeletedDescendants($this->root, null, false, array('_leftnode' => 'asc'), true));
        $this->assertEquals(3, $this->repo->getNotDeletedDescendants($this->root, null, false, array('_leftnode' => 'asc'), true)->count());
        $this->assertEquals(2, $this->repo->getNotDeletedDescendants($this->root, null, false, array('_leftnode' => 'asc'), true, 1)->getIterator()->count());
        $this->assertEquals(1, $this->repo->getNotDeletedDescendants($this->root, null, false, array('_leftnode' => 'asc'), true, 1, 1)->getIterator()->count());
        $this->assertEquals(array($this->root), $this->repo->getNotDeletedDescendants($this->root, null, true, array('_leftnode' => 'asc'), false, 0, 25, true));
    }

    /**
     * @ExpectedException \LogicalException
     */
    public function testGetRoot()
    {
        $this->assertEquals($this->root, $this->repo->getRoot($this->root->getSite()));
        $this->assertEquals($this->root, $this->repo->getRoot($this->root->getSite(), array(Page::STATE_HIDDEN)));
        $this->assertEquals($this->root, $this->repo->getRoot());
        $this->assertNull($this->repo->getRoot($this->root->getSite(), array(Page::STATE_ONLINE)));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getRoot
     */
    public function testGetRootException()
    {
        $site = new \BackBee\Site\Site();
        $site->setLabel('multi site')->setServerName('multi site');
        $this->getEntityManager()->persist($site);
        $this->getEntityManager()->flush($site);

        $this->repo->getRoot();
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getOnlineChildren
     */
    public function testGetOnlineChildren()
    {
        $this->assertEquals(array(), $this->repo->getOnlineChildren($this->root));

        $child1 = $this->repo->find('child1');
        $child3 = $this->repo->find('child3');
        $child1->setState(Page::STATE_ONLINE);
        $child3->setState(Page::STATE_ONLINE);
        $this->application->getEntityManager()->flush();

        $this->assertEquals(array($child3, $child1), $this->repo->getOnlineChildren($this->root));
        $this->assertEquals(array($child3), $this->repo->getOnlineChildren($this->root, 1));
        $this->assertEquals(array($child1, $child3), $this->repo->getOnlineChildren($this->root, null, array('_title')));
        $this->assertEquals(array($child3, $child1), $this->repo->getOnlineChildren($this->root, null, array('_title', 'desc')));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::getChildren
     */
    public function testGetChildren()
    {
        $child1 = $this->repo->find('child1');
        $child2 = $this->repo->find('child2');
        $child3 = $this->repo->find('child3');

        $this->assertEquals(array($child1, $child2, $child3), $this->repo->getChildren($this->root));
        $this->assertEquals(array($child3, $child2, $child1), $this->repo->getChildren($this->root, '_leftnode'));
        $this->assertEquals(array($child3, $child2, $child1), $this->repo->getChildren($this->root, '_title', 'desc'));
        $this->assertInstanceOf('Doctrine\ORM\Tools\Pagination\Paginator', $this->repo->getChildren($this->root, '_title', 'desc', array('start' => 0, 'limit' => 2)));
        $this->assertEquals(3, $this->repo->getChildren($this->root, '_title', 'desc', array('start' => 0, 'limit' => 2))->count());
        $this->assertEquals(1, $this->repo->getChildren($this->root, '_title', 'desc', array('start' => 1, 'limit' => 1))->getIterator()->count());

        $child1->setState(Page::STATE_ONLINE);
        $child3->setState(Page::STATE_ONLINE);
        $this->application->getEntityManager()->flush();

        $this->assertEquals(array($child1, $child3), $this->repo->getChildren($this->root, '_title', 'asc', array(), array(Page::STATE_ONLINE)));

        $tomorrow = new \DateTime('tomorrow');
        $yesterday = new \DateTime('yesterday');

        $this->assertEquals(array($child1, $child2, $child3), $this->repo->getChildren($this->root, '_title', 'asc', array(), array(), array('beforePubdateField' => $tomorrow->getTimestamp())));
        $this->assertEquals(array(), $this->repo->getChildren($this->root, '_title', 'asc', array(), array(), array('beforePubdateField' => $yesterday->getTimestamp())));
        $this->assertEquals(array(), $this->repo->getChildren($this->root, '_title', 'asc', array(), array(), array('afterPubdateField' => $tomorrow->getTimestamp())));
        $this->assertEquals(array($child1, $child2, $child3), $this->repo->getChildren($this->root, '_title', 'asc', array(), array(), array('afterPubdateField' => $yesterday->getTimestamp())));
        $this->assertEquals(array($child1, $child2, $child3), $this->repo->getChildren($this->root, '_title', 'asc', array(), array(), array('searchField' => 'child')));
        $this->assertEquals(array($child1), $this->repo->getChildren($this->root, '_title', 'asc', array(), array(), array('searchField' => '1')));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::countChildren
     */
    public function testCountChildren()
    {
        $child1 = $this->repo->find('child1');
        $child3 = $this->repo->find('child3');

        $this->assertEquals(3, $this->repo->countChildren($this->root));

        $child1->setState(Page::STATE_ONLINE);
        $child3->setState(Page::STATE_ONLINE);
        $this->application->getEntityManager()->flush();

        $this->assertEquals(2, $this->repo->countChildren($this->root, array(Page::STATE_ONLINE)));

        $tomorrow = new \DateTime('tomorrow');
        $yesterday = new \DateTime('yesterday');

        $this->assertEquals(3, $this->repo->countChildren($this->root, array(), array('beforePubdateField' => $tomorrow->getTimestamp())));
        $this->assertEquals(0, $this->repo->countChildren($this->root, array(), array('beforePubdateField' => $yesterday->getTimestamp())));
        $this->assertEquals(0, $this->repo->countChildren($this->root, array(), array('afterPubdateField' => $tomorrow->getTimestamp())));
        $this->assertEquals(3, $this->repo->countChildren($this->root, array(), array('afterPubdateField' => $yesterday->getTimestamp())));
        $this->assertEquals(3, $this->repo->countChildren($this->root, array(), array('searchField' => 'child')));
        $this->assertEquals(1, $this->repo->countChildren($this->root, array(), array('searchField' => '1')));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::toTrash
     */
    public function testToTrash()
    {
        $this->assertEquals(4, $this->repo->toTrash($this->root));

        $child1 = $this->repo->find('child1');
        $child2 = $this->repo->find('child2');
        $child3 = $this->repo->find('child3');

        $this->application->getEntityManager()->refresh($this->root);
        $this->application->getEntityManager()->refresh($child1);
        $this->application->getEntityManager()->refresh($child2);
        $this->application->getEntityManager()->refresh($child3);

        $this->assertEquals(Page::STATE_DELETED, $this->root->getState());
        $this->assertEquals(Page::STATE_DELETED, $child1->getState());
        $this->assertEquals(Page::STATE_DELETED, $child2->getState());
        $this->assertEquals(Page::STATE_DELETED, $child3->getState());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::likeAPage
     */
    public function testLikeAPage()
    {
        $this->assertNull($this->repo->likeAPage());

        $child1 = $this->repo->find('child1');
        $child2 = $this->repo->find('child2');
        $child3 = $this->repo->find('child3');

        $this->assertEquals(array($child1, $child2, $child3), $this->repo->likeAPage('child'));
        $this->assertEquals(array($child1, $child2, $child3), $this->repo->likeAPage('child', array()));
        $this->assertEquals(array($child2, $child3), $this->repo->likeAPage('child', array(1, 2)));
        $this->assertEquals(array($child2, $child3), $this->repo->likeAPage('child', array(1)));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::duplicate
     * @covers \BackBee\NestedNode\Repository\PageRepository::_copy
     */
    public function testDuplicate()
    {
        // Duplicate a root not recursively to new a one
        $root2 = $this->repo->duplicate($this->root, null, null, false);
        $this->assertInstanceOf('BackBee\NestedNode\Page', $root2);
        $this->assertNull($root2->getParent());
        $this->assertTrue($root2->isLeaf());

        // Duplicate a root not recursively to one of its descendant
        $child1 = $this->repo->find('child1');
        $child4 = $this->repo->duplicate($this->root, null, $child1, false);
        $this->assertInstanceOf('BackBee\NestedNode\Page', $child4);
        $this->assertEquals('root', $child4->getTitle());
        $this->assertEquals($child1, $child4->getParent());
        $this->assertTrue($root2->isLeaf());

        // Duplicate a page with a new title and a new parent
        $child5 = $this->repo->duplicate($child1, 'child5', $root2);
        $this->assertInstanceOf('BackBee\NestedNode\Page', $child5);
        $this->assertEquals('child5', $child5->getTitle());
        $this->assertEquals($root2, $child5->getParent());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::duplicate
     * @covers \BackBee\NestedNode\Repository\PageRepository::_copy_recursively
     */
    public function testDuplicateRecursively()
    {
        // Recursively duplicate a root to a new one
        $root2 = $this->repo->duplicate($this->root);
        $this->assertInstanceOf('BackBee\NestedNode\Page', $root2);
        $this->assertEquals('root', $root2->getTitle());
        $this->assertEquals($this->root->getLayout(), $root2->getLayout());
        $this->assertNull($root2->getParent());

        $descendants = $this->repo->getDescendants($this->root);
        $new_descendants = $this->repo->getDescendants($root2);
        $this->assertEquals(count($descendants), count($new_descendants));

        for ($i = 0; $i < count($descendants); $i++) {
            $this->assertEquals($descendants[$i]->getTitle(), $new_descendants[$i]->getTitle());
        }

        // Duplicate a page with a new title and a new parent
        $child1 = $this->repo->find('child1');
        $child5 = $this->repo->duplicate($child1, 'child5', $root2);
        $this->assertInstanceOf('BackBee\NestedNode\Page', $child5);
        $this->assertEquals('child5', $child5->getTitle());
        $this->assertEquals($root2, $child5->getParent());

        // Duplicate children except deleted ones
        $child5->setState(Page::STATE_DELETED);
        $root3 = $this->repo->duplicate($root2);
        $this->assertEquals(count($this->repo->getDescendants($root2)) - 1, count($this->repo->getDescendants($root3)));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::duplicate
     * @covers \BackBee\NestedNode\Repository\PageRepository::_updateRelatedPostCloning
     * @covers \BackBee\NestedNode\Repository\PageRepository::_updateMainNodePostCloning
     */
    public function testDuplicateWithToken()
    {
        $token = new \BackBee\Security\Token\BBUserToken();
        $token->setUser(new \BackBee\Security\User('user'));

        $root2 = $this->repo->duplicate($this->root, null, null, true, $token);
        $descendants = $this->repo->getDescendants($root2);
        $expected_datas = array(
            'pages' => array(
                'root' => $root2,
                'child1' => $descendants[2],
                'child2' => $descendants[1],
                'child3' => $descendants[0],
            ),
            'contents' => array(
                $this->root->getContentSet()->getUid() => $root2->getContentSet(),
                $this->root->getContentSet()->first()->getUid() => $root2->getContentSet()->first(),
                $this->root->getContentSet()->first()->last()->getUid() => $root2->getContentSet()->first()->last(),
            ),
        );

        $this->assertEquals($expected_datas, $root2->cloning_datas);
        $this->assertEquals($this->root, $this->root->getContentSet()->first()->last()->getMainNode());
        $this->assertEquals($root2, $root2->getContentSet()->first()->last()->getMainNode());
        $this->assertInstanceOf('BackBee\ClassContent\Revision', $root2->getContentSet()->first()->last()->getDraft());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::_copy
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testDuplicatePageIn0neOfItsDescendants()
    {
        $child1 = $this->repo->find('child1');
        $this->repo->duplicate($this->root, null, $child1);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageRepository::duplicate
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testDuplicateDeletedPage()
    {
        $this->root->setState(Page::STATE_HIDDEN + Page::STATE_DELETED);
        $this->repo->duplicate($this->root);
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        $this->initAutoload();
        $this->application = $this->getBBApp();
        $this->initDb($this->application);
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
     * Sets the NestedNode Repository.
     *
     * @return \BackBee\NestedNode\Tests\Repository\NestedNodeRepositoryTest
     */
    private function _setRepo()
    {
        $this->repo = $this->getEntityManager()
                ->getRepository('BackBee\NestedNode\Page');

        PageRepository::$config = array(
            'nestedNodeCalculateAsync' => false,
        );

        PageQueryBuilder::$config = array(
            'dateSchemeForPublishing' => 'Y-m-d H:i:s',
        );

        return $this;
    }

    /**
     * Builds a default set of layout zones.
     *
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
            $asidezone,
        );

        return $data;
    }
}
