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

use BackBuilder\Tests\TestCase;
use BackBuilder\Site\Site;
use BackBuilder\Site\Layout;
use BackBuilder\NestedNode\Page;

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
        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $this->repo->createQueryBuilder('p'));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::findBy
     */
    public function testFindBy()
    {
        $this->assertEquals(array($this->root), $this->repo->findBy(array('_title' => 'root')));
        $this->assertEquals(6, count($this->repo->findBy(array('_site' => $this->root->getSite()))));
        $this->assertEquals(array($this->root), $this->repo->findBy(array('_title' => 'root'), array('_leftnode' => 'ASC'), 1, 0));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::findOneBy
     */
    public function testFindOneBy()
    {
        $this->assertEquals($this->root, $this->repo->findOneBy(array('_title' => 'root')));
        $this->assertEquals($this->root, $this->repo->findOneBy(array('_title' => 'root'), array('_leftnode' => 'ASC')));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getAncestor
     */
    public function testGetAncestor()
    {
        $page1 = $this->repo->find('page1');
        $section1 = $this->repo->find('section1');

        $this->assertEquals($this->root, $this->repo->getAncestor($page1));
        $this->assertEquals($this->root, $this->repo->getAncestor($page1, 0));
        $this->assertEquals($section1, $this->repo->getAncestor($page1, 1));
        $this->assertEquals($page1, $this->repo->getAncestor($page1, 2));
        $this->assertNull($this->repo->getAncestor($page1, 3));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getAncestors
     */
    public function testGetAncestors()
    {
        $page1 = $this->repo->find('page1');
        $section1 = $this->repo->find('section1');

        $this->assertEquals(array($this->root, $section1), $this->repo->getAncestors($page1));
        $this->assertEquals(array($section1), $this->repo->getAncestors($page1, 1));
        $this->assertEquals(array($section1, $page1), $this->repo->getAncestors($page1, 1, true));
        $this->assertEquals(array(), $this->repo->getAncestors($page1, 3));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOnlinePrevSibling
     */
    public function testGetOnlinePrevSibling()
    {
        PageQueryBuilder::$config['dateSchemeForPublishing'] = 'Y-m-d H:i:s';

        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $page3 = $this->repo->find('page3');

        $this->assertNull($this->repo->getOnlinePrevSibling($section1));
        $this->assertNull($this->repo->getOnlinePrevSibling($section2));
        $this->assertNull($this->repo->getOnlinePrevSibling($page1));
        $this->assertNull($this->repo->getOnlinePrevSibling($page2));
        $this->assertNull($this->repo->getOnlinePrevSibling($page3));

        $section2->setState(Page::STATE_ONLINE);
        $section1->setState(Page::STATE_ONLINE);
        $page1->setState(Page::STATE_ONLINE);
        $page2->setState(Page::STATE_ONLINE);
        $page3->setState(Page::STATE_ONLINE);
        $this->getEntityManager()->flush();

        $this->assertEquals($section2, $this->repo->getOnlinePrevSibling($section1));
        $this->assertNull($this->repo->getOnlinePrevSibling($section2));
        $this->assertEquals($page2, $this->repo->getOnlinePrevSibling($page1));
        $this->assertNull($this->repo->getOnlinePrevSibling($page2));
        $this->assertEquals($section1, $this->repo->getOnlinePrevSibling($page3));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOnlineSiblingsByLayout
     */
    public function testGetOnlineSiblingsByLayout()
    {
        PageQueryBuilder::$config['dateSchemeForPublishing'] = 'Y-m-d H:i:s';

        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $page3 = $this->repo->find('page3');

        $this->assertEquals(array(), $this->repo->getOnlineSiblingsByLayout($section1, $this->root->getLayout()));
        $this->assertEquals(array(), $this->repo->getOnlineSiblingsByLayout($section2, $this->root->getLayout()));
        $this->assertEquals(array(), $this->repo->getOnlineSiblingsByLayout($page1, $this->root->getLayout()));
        $this->assertEquals(array(), $this->repo->getOnlineSiblingsByLayout($page2, $this->root->getLayout()));
        $this->assertEquals(array(), $this->repo->getOnlineSiblingsByLayout($page3, $this->root->getLayout()));

        $section2->setState(Page::STATE_ONLINE);
        $section1->setState(Page::STATE_ONLINE);
        $page1->setState(Page::STATE_ONLINE);
        $page2->setState(Page::STATE_ONLINE);
        $page3->setState(Page::STATE_ONLINE);
        $this->getEntityManager()->flush();

        $this->assertEquals(array($section2, $page3), $this->repo->getOnlineSiblingsByLayout($section1, $this->root->getLayout(), false, array('_position' => 'ASC', '_leftnode' => 'ASC')));
        $this->assertEquals(array($section1, $page3), $this->repo->getOnlineSiblingsByLayout($section2, $this->root->getLayout(), false, array('_position' => 'ASC', '_leftnode' => 'ASC')));
        $this->assertEquals(array($page2), $this->repo->getOnlineSiblingsByLayout($page1, $this->root->getLayout(), false, array('_position' => 'ASC', '_leftnode' => 'ASC')));
        $this->assertEquals(array($page1), $this->repo->getOnlineSiblingsByLayout($page2, $this->root->getLayout(), false, array('_position' => 'ASC', '_leftnode' => 'ASC')));
        $this->assertEquals(array($section2, $section1), $this->repo->getOnlineSiblingsByLayout($page3, $this->root->getLayout(), false, array('_position' => 'ASC', '_leftnode' => 'ASC')));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOnlineNextSibling
     */
    public function testGetOnlineNextSibling()
    {
        PageQueryBuilder::$config['dateSchemeForPublishing'] = 'Y-m-d H:i:s';

        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $page3 = $this->repo->find('page3');

        $this->assertNull($this->repo->getOnlineNextSibling($section1));
        $this->assertNull($this->repo->getOnlineNextSibling($section2));
        $this->assertNull($this->repo->getOnlineNextSibling($page1));
        $this->assertNull($this->repo->getOnlineNextSibling($page2));
        $this->assertNull($this->repo->getOnlineNextSibling($page3));

        $section2->setState(Page::STATE_ONLINE);
        $section1->setState(Page::STATE_ONLINE);
        $page1->setState(Page::STATE_ONLINE);
        $page2->setState(Page::STATE_ONLINE);
        $page3->setState(Page::STATE_ONLINE);
        $this->getEntityManager()->flush();

        $this->assertEquals($page3, $this->repo->getOnlineNextSibling($section1));
        $this->assertEquals($section1, $this->repo->getOnlineNextSibling($section2));
        $this->assertNull($this->repo->getOnlineNextSibling($page1));
        $this->assertEquals($page1, $this->repo->getOnlineNextSibling($page2));
        $this->assertNull($this->repo->getOnlineNextSibling($page3));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::insertNodeAsFirstChildOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::insertNodeAsLastChildOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::insertNode
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getMaxPosition
     */
    public function testInsertNode()
    {
        $newpage1 = new Page('new-page1', array('title' => 'new-page1'));
        $newpage1->setLayout(new Layout());
        $this->repo->insertNodeAsFirstChildOf($newpage1, $this->root);

        foreach ($this->repo->findAll() as $node) {
            $this->getEntityManager()->refresh($node);
        }

        $page3 = $this->repo->find('page3');

        $this->assertFalse($newpage1->hasMainSection());
        $this->assertEquals($this->root, $newpage1->getParent());
        $this->assertEquals(1, $newpage1->getLevel());
        $this->assertEquals(1, $newpage1->getPosition());
        $this->assertEquals(6, $this->root->getRightnode());
        $this->assertEquals(2, $page3->getPosition());

        $section2 = $this->repo->find('section2');
        $newpage2 = new Page('new-page2', array('title' => 'new-page2'));
        $newpage2->setLayout(new Layout());
        $this->repo->insertNodeAsLastChildOf($newpage2, $section2, true);

        foreach ($this->repo->findAll() as $node) {
            $this->getEntityManager()->refresh($node);
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

        $section1 = $this->repo->find('section1');
        $newpage3 = new Page('new-page3', array('title' => 'new-page3'));
        $newpage3->setLayout(new Layout());

        $this->repo->insertNodeAsLastChildOf($newpage3, $section1, false);
        foreach ($this->repo->findAll() as $node) {
            $this->getEntityManager()->refresh($node);
        }

        $this->assertEquals(1, $newpage3->getPosition());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::insertNode
     * @expectedException BackBuilder\Exception\InvalidArgumentException
     */
    public function testInsertNodeInNonSection()
    {
        $page1 = $this->repo->find('page1');
        $newpage = new Page('new-page', array('title' => 'new-page'));
        $newpage->setLayout(new Layout());
        $this->repo->insertNode($newpage, $page1, 1);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getDescendants
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOrderingDescendants
     */
    public function testGetDescendants()
    {
        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $page3 = $this->repo->find('page3');

        $this->assertEquals(array(), $this->repo->getDescendants($page1));
        $this->assertEquals(array($page3, $section2, $section1, $page2, $page1), $this->repo->getDescendants($this->root));
        $this->assertEquals(array($section2, $section1, $page3), $this->repo->getDescendants($this->root, 1));
        $this->assertEquals(array($page3, $section2, $section1, $page2, $page1), $this->repo->getDescendants($this->root, 2));
        $this->assertEquals(array($this->root, $page3, $section2, $section1, $page2, $page1), $this->repo->getDescendants($this->root, 2, true));
        $this->assertEquals(array($this->root, $page3, $section2, $section1, $page2, $page1), $this->repo->getDescendants($this->root, 2, true, array()));
        $this->assertEquals(array($page1, $page2, $page3, $this->root, $section1, $section2), $this->repo->getDescendants($this->root, 2, true, array('_title' => 'ASC')));
        $this->assertEquals(array($this->root, $section1, $section2), $this->repo->getDescendants($this->root, 2, true, array('_title' => 'ASC'), false, null, null, true));

        $result = $this->repo->getDescendants($this->root, 2, true, array(), true, 1, 2);
        $this->assertInstanceOf('Doctrine\ORM\Tools\Pagination\Paginator', $result);
        $this->assertEquals(6, $result->count());
        $this->assertEquals(2, $result->getIterator()->count());
        $this->assertEquals(1, $result->getQuery()->getFirstResult());
        $this->assertEquals(2, $result->getQuery()->getMaxResults());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOnlineDescendants
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOrderingDescendants
     */
    public function testGetOnlineDescendants()
    {
        PageQueryBuilder::$config['dateSchemeForPublishing'] = 'Y-m-d H:i:s';

        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $page3 = $this->repo->find('page3');

        $this->root->setState(Page::STATE_ONLINE);
        $section2->setState(Page::STATE_ONLINE);
        $section1->setState(Page::STATE_ONLINE);
        $page1->setState(Page::STATE_ONLINE);
        $page2->setState(Page::STATE_ONLINE);
        $page3->setState(Page::STATE_ONLINE);
        $this->getEntityManager()->flush();

        $this->assertEquals(array(), $this->repo->getOnlineDescendants($page1));
        $this->assertEquals(array($page3, $section2, $section1, $page2, $page1), $this->repo->getOnlineDescendants($this->root));
        $this->assertEquals(array($section2, $section1, $page3), $this->repo->getOnlineDescendants($this->root, 1));
        $this->assertEquals(array($page3, $section2, $section1, $page2, $page1), $this->repo->getOnlineDescendants($this->root, 2));
        $this->assertEquals(array($this->root, $page3, $section2, $section1, $page2, $page1), $this->repo->getOnlineDescendants($this->root, 2, true));
        $this->assertEquals(array($this->root, $page3, $section2, $section1, $page2, $page1), $this->repo->getOnlineDescendants($this->root, 2, true, array()));
        $this->assertEquals(array($page1, $page2, $page3, $this->root, $section1, $section2), $this->repo->getOnlineDescendants($this->root, 2, true, array('_title' => 'ASC')));
        $this->assertEquals(array($this->root, $section1, $section2), $this->repo->getOnlineDescendants($this->root, 2, true, array('_title' => 'ASC'), false, null, null, true));

        $result = $this->repo->getOnlineDescendants($this->root, 2, true, array(), true, 1, 2);
        $this->assertInstanceOf('Doctrine\ORM\Tools\Pagination\Paginator', $result);
        $this->assertEquals(6, $result->count());
        $this->assertEquals(2, $result->getIterator()->count());
        $this->assertEquals(1, $result->getQuery()->getFirstResult());
        $this->assertEquals(2, $result->getQuery()->getMaxResults());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getVisibleDescendants
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOrderingDescendants
     */
    public function testGetVisibleDescendants()
    {
        PageQueryBuilder::$config['dateSchemeForPublishing'] = 'Y-m-d H:i:s';

        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $page3 = $this->repo->find('page3');

        $this->root->setState(Page::STATE_ONLINE);
        $section2->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
        $section1->setState(Page::STATE_ONLINE);
        $page1->setState(Page::STATE_ONLINE);
        $page2->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
        $page3->setState(Page::STATE_ONLINE);
        $this->getEntityManager()->flush();

        $this->assertEquals(array(), $this->repo->getVisibleDescendants($page1));
        $this->assertEquals(array($page3, $section1, $page1), $this->repo->getVisibleDescendants($this->root));
        $this->assertEquals(array($section1, $page3), $this->repo->getVisibleDescendants($this->root, 1));
        $this->assertEquals(array($page3, $section1, $page1), $this->repo->getVisibleDescendants($this->root, 2));
        $this->assertEquals(array($this->root, $page3, $section1, $page1), $this->repo->getVisibleDescendants($this->root, 2, true));
        $this->assertEquals(array($this->root, $page3, $section1, $page1), $this->repo->getVisibleDescendants($this->root, 2, true, array()));
        $this->assertEquals(array($page1, $page3, $this->root, $section1), $this->repo->getVisibleDescendants($this->root, 2, true, array('_title' => 'ASC')));
        $this->assertEquals(array($this->root, $section1), $this->repo->getVisibleDescendants($this->root, 2, true, array('_title' => 'ASC'), false, null, null, true));

        $result = $this->repo->getVisibleDescendants($this->root, 2, true, array(), true, 1, 2);
        $this->assertInstanceOf('Doctrine\ORM\Tools\Pagination\Paginator', $result);
        $this->assertEquals(4, $result->count());
        $this->assertEquals(2, $result->getIterator()->count());
        $this->assertEquals(1, $result->getQuery()->getFirstResult());
        $this->assertEquals(2, $result->getQuery()->getMaxResults());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getNotDeletedDescendants
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getOrderingDescendants
     */
    public function testgetNotDeletedDescendants()
    {
        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $page3 = $this->repo->find('page3');

        $section2->setState(Page::STATE_DELETED);
        $page2->setState(Page::STATE_DELETED);
        $this->getEntityManager()->flush();

        $this->assertEquals(array(), $this->repo->getNotDeletedDescendants($page1));
        $this->assertEquals(array($page3, $section1, $page1), $this->repo->getNotDeletedDescendants($this->root));
        $this->assertEquals(array($section1, $page3), $this->repo->getNotDeletedDescendants($this->root, 1));
        $this->assertEquals(array($page3, $section1, $page1), $this->repo->getNotDeletedDescendants($this->root, 2));
        $this->assertEquals(array($this->root, $page3, $section1, $page1), $this->repo->getNotDeletedDescendants($this->root, 2, true));
        $this->assertEquals(array($this->root, $page3, $section1, $page1), $this->repo->getNotDeletedDescendants($this->root, 2, true, array()));
        $this->assertEquals(array($page1, $page3, $this->root, $section1), $this->repo->getNotDeletedDescendants($this->root, 2, true, array('_title' => 'ASC')));
        $this->assertEquals(array($this->root, $section1), $this->repo->getNotDeletedDescendants($this->root, 2, true, array('_title' => 'ASC'), false, null, null, true));

        $result = $this->repo->getNotDeletedDescendants($this->root, 2, true, array(), true, 1, 2);
        $this->assertInstanceOf('Doctrine\ORM\Tools\Pagination\Paginator', $result);
        $this->assertEquals(4, $result->count());
        $this->assertEquals(2, $result->getIterator()->count());
        $this->assertEquals(1, $result->getQuery()->getFirstResult());
        $this->assertEquals(2, $result->getQuery()->getMaxResults());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsFirstChildOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsChildOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::movePageAsChildOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveSectionAsChildOf
     */
    public function testMoveAsFirstChidOf()
    {
        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page3 = $this->repo->find('page3');

        $this->assertEquals($page3, $this->repo->moveAsFirstChildOf($page3, $section1));
        $this->assertEquals($section1, $page3->getParent());
        $this->assertEquals(1, $page3->getPosition());
        $this->assertEquals(2, $page3->getLevel());
        $this->getEntityManager()->refresh($section1);
        $this->getEntityManager()->flush($page3);

        $this->assertEquals($section1, $this->repo->moveAsFirstChildOf($section1, $section2));
        $this->getEntityManager()->refresh($section2);
        $this->getEntityManager()->refresh($page3);

        $this->assertEquals($section2, $section1->getParent());
        $this->assertEquals(2, $section1->getLevel());
        $this->assertEquals(3, $section1->getLeftnode());
        $this->assertEquals(4, $section1->getRightnode());
        $this->assertEquals(2, $section2->getLeftnode());
        $this->assertEquals(5, $section2->getRightnode());
        $this->assertEquals(3, $page3->getLevel());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsFirstChildOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsChildOf
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testMoveAsFirstChidOfNonSection()
    {
        $section1 = $this->repo->find('section1');
        $page1 = $this->repo->find('page1');

        $this->repo->moveAsFirstChildOf($section1, $page1);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsLastChildOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsChildOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::movePageAsChildOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveSectionAsChildOf
     */
    public function testMoveAsLastChidOf()
    {
        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');

        $this->assertEquals($page2, $this->repo->moveAsLastChildOf($page2, $section2));
        $this->getEntityManager()->refresh($section2);
        $this->getEntityManager()->refresh($page1);
        $this->getEntityManager()->flush($page2);

        $this->assertEquals($section2, $page2->getParent());
        $this->assertEquals(1, $page2->getPosition());
        $this->assertEquals(2, $page2->getLevel());
        $this->assertEquals(1, $page1->getPosition());

        $this->assertEquals($section2, $this->repo->moveAsFirstChildOf($section2, $section1));
        $this->getEntityManager()->refresh($section1);
        $this->getEntityManager()->refresh($page1);
        $this->getEntityManager()->refresh($page2);

        $this->assertEquals($section1, $section2->getParent());
        $this->assertEquals(2, $section2->getLevel());
        $this->assertEquals(3, $section2->getLeftnode());
        $this->assertEquals(4, $section2->getRightnode());
        $this->assertEquals(2, $section1->getLeftnode());
        $this->assertEquals(5, $section1->getRightnode());
        $this->assertEquals(3, $page2->getLevel());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsLastChildOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsChildOf
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testMoveAsLastChildOfNonSection()
    {
        $section1 = $this->repo->find('section1');
        $page1 = $this->repo->find('page1');

        $this->repo->moveAsLastChildOf($section1, $page1);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsPrevSiblingOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsSiblingOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::movePageAsSiblingOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveSectionAsSiblingOf
     */
    public function testMoveAsPrevSiblingOf()
    {
        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $page3 = $this->repo->find('page3');

        $this->assertEquals($page3, $this->repo->moveAsPrevSiblingOf($page3, $page1));
        $this->getEntityManager()->refresh($page1);
        $this->getEntityManager()->refresh($page2);
        $this->getEntityManager()->flush($page3);

        $this->assertEquals($section1, $page3->getParent());
        $this->assertEquals(2, $page3->getLevel());
        $this->assertEquals(1, $page2->getPosition());
        $this->assertEquals(2, $page3->getPosition());
        $this->assertEquals(3, $page1->getPosition());

        $this->assertEquals($section2, $this->repo->moveAsPrevSiblingOf($section2, $section1));
        $this->getEntityManager()->refresh($section1);
        $this->getEntityManager()->flush($section2);

        $this->assertEquals(1, $this->root->getLeftnode());
        $this->assertEquals(6, $this->root->getRightnode());
        $this->assertEquals(2, $section2->getLeftnode());
        $this->assertEquals(3, $section2->getRightnode());
        $this->assertEquals(4, $section1->getLeftnode());
        $this->assertEquals(5, $section1->getRightnode());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsNextSiblingOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsSiblingOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::movePageAsSiblingOf
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveSectionAsSiblingOf
     */
    public function testMoveAsNextSiblingOf()
    {
        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $page3 = $this->repo->find('page3');

        $this->assertEquals($page3, $this->repo->moveAsNextSiblingOf($page3, $page2));
        $this->getEntityManager()->refresh($page1);
        $this->getEntityManager()->refresh($page2);
        $this->getEntityManager()->flush($page3);

        $this->assertEquals($section1, $page3->getParent());
        $this->assertEquals(2, $page3->getLevel());
        $this->assertEquals(1, $page2->getPosition());
        $this->assertEquals(2, $page3->getPosition());
        $this->assertEquals(3, $page1->getPosition());

        $this->assertEquals($section1, $this->repo->moveAsNextSiblingOf($section1, $section2));
        $this->getEntityManager()->refresh($section2);
        $this->getEntityManager()->flush($section1);

        $this->assertEquals(1, $this->root->getLeftnode());
        $this->assertEquals(6, $this->root->getRightnode());
        $this->assertEquals(2, $section2->getLeftnode());
        $this->assertEquals(3, $section2->getRightnode());
        $this->assertEquals(4, $section1->getLeftnode());
        $this->assertEquals(5, $section1->getRightnode());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveAsSiblingOf
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testMoveAsSiblingOfRoot()
    {
        $section1 = $this->repo->find('section1');
        $this->repo->moveAsPrevSiblingOf($section1, $this->root);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::movePageAsSiblingOf
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testMoveNonSectionAsSiblingOfSection()
    {
        $section1 = $this->repo->find('section1');
        $page1 = $this->repo->find('page1');
        $this->repo->moveAsPrevSiblingOf($page1, $section1);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::moveSectionAsSiblingOf
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testMoveSectionAsSiblingOfNonSection()
    {
        $section1 = $this->repo->find('section1');
        $page1 = $this->repo->find('page1');
        $this->repo->moveAsPrevSiblingOf($section1, $page1);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::getRoot
     */
    public function testGetRoot()
    {
        $this->assertEquals($this->root, $this->repo->getRoot($this->root->getSite()));
        $this->assertEquals($this->root, $this->repo->getRoot($this->root->getSite(), array(Page::STATE_HIDDEN)));
        $this->assertNull($this->repo->getRoot($this->root->getSite(), array(Page::STATE_ONLINE)));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::toTrash
     */
    public function testToTrash()
    {
        $section1 = $this->repo->find('section1');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $page3 = $this->repo->find('page3');

        $this->assertEquals(1, $this->repo->toTrash($page3));
        $this->assertEquals(Page::STATE_DELETED, $page3->getState());

        $this->assertEquals(3, $this->repo->toTrash($section1));
        $this->getEntityManager()->refresh($section1);
        $this->getEntityManager()->refresh($page1);
        $this->getEntityManager()->refresh($page2);
        $this->assertEquals(Page::STATE_DELETED, $section1->getState());
        $this->assertEquals(Page::STATE_DELETED, $page1->getState());
        $this->assertEquals(Page::STATE_DELETED, $page2->getState());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::copy
     */
    public function testCopy()
    {
        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');

        $new_page1 = $this->invokeMethod($this->repo, 'copy', array($page1));
        $this->assertInstanceOf('BackBuilder\NestedNode\Page', $new_page1);
        $this->assertTrue($this->getEntityManager()->contains($new_page1));
        $this->assertFalse($new_page1->hasMainSection());
        $this->assertEquals('page1', $new_page1->getTitle());
        $this->assertEquals($page1->getParent(), $new_page1->getParent());

        $new_page2 = $this->invokeMethod($this->repo, 'copy', array($page1, 'new_title'));
        $this->assertInstanceOf('BackBuilder\NestedNode\Page', $new_page2);
        $this->assertEquals('new_title', $new_page2->getTitle());

        $new_page3 = $this->invokeMethod($this->repo, 'copy', array($page1, 'new_title', $section2));
        $this->assertInstanceOf('BackBuilder\NestedNode\Page', $new_page3);
        $this->assertEquals($section2, $new_page3->getParent());

        $new_section = $this->invokeMethod($this->repo, 'copy', array($section1, 'new_section', $this->root));
        $this->assertInstanceOf('BackBuilder\NestedNode\Page', $new_section);
        $this->assertTrue($new_section->hasMainSection());
        $this->assertEquals($this->root->getSection(), $new_section->getSection()->getParent());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::copy
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testCopyDeletedPage()
    {
        $page1 = $this->repo->find('page1');
        $page1->setState(Page::STATE_DELETED);
        $this->invokeMethod($this->repo, 'copy', array($page1));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::duplicate
     */
    public function testDuplicate()
    {
        // Duplicate a root not recursively to new a one
        $clone1 = $this->repo->duplicate($this->root, null, null, false);
        $this->assertInstanceOf('BackBuilder\NestedNode\Page', $clone1);
        $this->assertNull($clone1->getParent());
        $this->assertEquals('root', $clone1->getTitle());
        $this->assertTrue($clone1->hasMainSection());
        $this->assertEquals(1, $clone1->getLeftnode());
        $this->assertEquals(2, $clone1->getRightnode());
        $this->assertNotEquals($this->root->getContentSet(), $clone1->getContentSet());

        // Duplicate a root not recursively to one of its descendant
        $section1 = $this->repo->find('section1');
        $clone2 = $this->repo->duplicate($this->root, null, $section1, false);
        $this->assertInstanceOf('BackBuilder\NestedNode\Page', $clone2);
        $this->assertEquals($section1, $clone2->getParent());
        $this->assertTrue($clone2->hasMainSection());

        // Duplicate a section not recursively to another one
        $section2 = $this->repo->find('section2');
        $section3 = $this->repo->duplicate($section2, null, $section1, false);
        $this->assertInstanceOf('BackBuilder\NestedNode\Page', $section3);
        $this->assertEquals($section1, $section3->getParent());
        $this->assertTrue($section3->hasMainSection());

        // Duplicate a page in a section with a new title
        $page3 = $this->repo->find('page3');
        $page4 = $this->repo->duplicate($page3, 'page4', $section1, false);
        $this->assertInstanceOf('BackBuilder\NestedNode\Page', $page4);
        $this->assertEquals($section1, $page4->getParent());
        $this->assertFalse($page4->hasMainSection());
        $this->assertEquals('page4', $page4->getTitle());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::duplicateRecursively
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::updateMainNodePostCloning
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::updateRelatedPostCloning
     */
    public function testDuplicateRecursively()
    {
        // Initialize some mainnodes and circular related links
        $descendants = $this->repo->getDescendants($this->root, null, true);
        foreach ($descendants as $child) {
            $child->getContentSet()->first()->setMainNode($child);
        }
        $page1 = $this->repo->find('page1');
        $page3 = $this->repo->find('page3');
        $page1->getContentSet()->first()->push($page3->getContentSet()->first());
        $page3->getContentSet()->first()->push($page1->getContentSet()->first());
        $this->getEntityManager()->flush();

        // Recursively duplicate a tree
        $clone1 = $this->repo->duplicate($this->root);
        $this->assertInstanceOf('BackBuilder\NestedNode\Page', $clone1);
        $this->assertNull($clone1->getParent());
        $this->assertEquals('root', $clone1->getTitle());
        $this->assertTrue($clone1->hasMainSection());

        $new_descendants = $this->repo->getDescendants($clone1, null, true);
        $this->assertEquals(count($descendants), count($new_descendants));

        $new_page1 = $new_page3 = null;
        for ($i = 0; $i < count($descendants); $i++) {
            $this->getEntityManager()->refresh($new_descendants[$i]);
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
        $section1 = $this->repo->find('section1');
        $section1->setState(Page::STATE_DELETED);
        $clone2 = $this->repo->duplicate($this->root);
        $this->assertEquals(count($this->repo->getDescendants($this->root)) - 3, count($this->repo->getDescendants($clone2)));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::duplicateRecursively
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testDuplicatePageIn0neOfItsDescendants()
    {
        $section1 = $this->repo->find('section1');
        $this->repo->duplicate($this->root, null, $section1);
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::saveWithSection
     */
    public function testSaveWithSection()
    {
        $this->assertEquals($this->root, $this->repo->saveWithSection($this->root));

        $page2 = $this->repo->find('page2');
        $this->assertEquals($page2, $this->repo->saveWithSection($page2));

        $this->getEntityManager()->flush();
        $this->getEntityManager()->refresh($page2);

        $this->assertTrue($page2->hasMainSection());
        $this->assertEquals(0, $page2->getPosition());
        $this->assertEquals(2, $page2->getLevel());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageRepository::shiftPosition
     */
    public function testShiftPosition()
    {
        $this->assertEquals($this->repo, $this->invokeMethod($this->repo, 'shiftPosition', array($this->root, 1)));
        $this->assertEquals(0, $this->root->getPosition());

        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $this->getEntityManager()->refresh($page1);
        $this->getEntityManager()->refresh($page2);

        $this->assertEquals($this->repo, $this->invokeMethod($this->repo, 'shiftPosition', array($page2, 1)));
        $this->assertEquals(2, $page2->getPosition());

        $this->getEntityManager()->refresh($page1);
        $this->assertEquals(3, $page1->getPosition());

        $this->assertEquals($this->repo, $this->invokeMethod($this->repo, 'shiftPosition', array($page2, 1, true)));
        $this->assertEquals(2, $page2->getPosition());

        $this->getEntityManager()->refresh($page1);
        $this->assertEquals(4, $page1->getPosition());
    }

    public function testShiftLevel()
    {
        $section1 = $this->repo->find('section1');
        $section2 = $this->repo->find('section2');
        $page1 = $this->repo->find('page1');
        $page2 = $this->repo->find('page2');
        $page3 = $this->repo->find('page3');

        $this->assertEquals($this->repo, $this->invokeMethod($this->repo, 'shiftLevel', array($page3, 1, true)));
        $this->getEntityManager()->refresh($page3);
        $this->assertEquals(1, $page3->getLevel());

        $this->assertEquals($this->repo, $this->invokeMethod($this->repo, 'shiftLevel', array($page3, 1, false)));
        $this->getEntityManager()->refresh($page3);
        $this->assertEquals(2, $page3->getLevel());

        $this->invokeMethod($this->repo, 'shiftLevel', array($this->root, 2));
        foreach ($this->repo->findAll() as $node) {
            $this->getEntityManager()->refresh($node);
        }
        $this->assertEquals(2, $this->root->getLevel());
        $this->assertEquals(3, $section1->getLevel());
        $this->assertEquals(3, $section2->getLevel());
        $this->assertEquals(4, $page1->getLevel());
        $this->assertEquals(4, $page2->getLevel());
        $this->assertEquals(4, $page3->getLevel());

        $this->invokeMethod($this->repo, 'shiftLevel', array($this->root, -2, true));
        foreach ($this->repo->findAll() as $node) {
            $this->getEntityManager()->refresh($node);
        }
        $this->assertEquals(2, $this->root->getLevel());
        $this->assertEquals(1, $section1->getLevel());
        $this->assertEquals(1, $section2->getLevel());
        $this->assertEquals(2, $page1->getLevel());
        $this->assertEquals(2, $page2->getLevel());
        $this->assertEquals(2, $page3->getLevel());
    }

    /**
     * Sets up the fixture
     */
    protected function setUp()
    {
        $this->initAutoload();
        $this->application = $this->getBBApp();

        $em = $this->getEntityManager();
        $st = new \Doctrine\ORM\Tools\SchemaTool($em);
        $st->updateSchema(array(
            $em->getClassMetaData('BackBuilder\ClassContent\AClassContent'),
            $em->getClassMetaData('BackBuilder\ClassContent\Indexes\IdxContentContent'),
            $em->getClassMetaData('BackBuilder\ClassContent\Indexes\IdxSiteContent'),
            $em->getClassMetaData('BackBuilder\ClassContent\Indexes\OptContentByModified'),
            $em->getClassMetaData('BackBuilder\Site\Site'),
            $em->getClassMetaData('BackBuilder\Site\Layout'),
            $em->getClassMetaData('BackBuilder\NestedNode\Page'),
            $em->getClassMetaData('BackBuilder\NestedNode\Section'),
        ));

        $this->application->start();

        $site = new Site('site-test', array('label' => 'site-test'));
        $this->getEntityManager()->persist($site);

        $layout = new Layout('layout-test', array('label' => 'layout-test', 'path' => 'layout-path'));
        $layout->setDataObject($this->getDefaultLayoutZones());
        $this->getEntityManager()->persist($layout);

        $this->root = new Page('root', array('title' => 'root'));
        $this->root->setSite($site)
                ->setLayout($layout);

        $this->getEntityManager()->persist($this->root);
        $this->getEntityManager()->flush();

        $this->repo = $this->getEntityManager()->getRepository('BackBuilder\NestedNode\Page');

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
        $this->getEntityManager()->refresh($this->root);
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
            $asidezone,
        );

        return $data;
    }

    /**
     * Add a new page in mock tree
     * @param string $uid
     * @param \BackBuilder\Site\Layout $layout
     * @param \BackBuilder\NestedNode\Page $parent
     * @param boolean $section                       If TRUE, the page is inserted with a section
     * @return \BackBuilder\NestedNode\Page
     */
    private function addPage($uid, Layout $layout, Page $parent, $section = false)
    {
        $page = new Page($uid, array('title' => $uid));
        $page->setLayout($layout);
        $this->repo->insertNodeAsFirstChildOf($page, $parent, $section);
        $this->getEntityManager()->persist($page);
        $this->getEntityManager()->flush($page);

        foreach ($this->repo->findAll() as $node) {
            $this->getEntityManager()->refresh($node);
        }

        return $page;
    }

}
