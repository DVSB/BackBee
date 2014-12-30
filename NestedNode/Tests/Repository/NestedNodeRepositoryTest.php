<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

use BackBee\NestedNode\Repository\NestedNodeRepository;
use BackBee\NestedNode\Tests\Mock\MockNestedNode;
use BackBee\Tests\TestCase;

/**
 * @category    BackBee
 * @package     BackBee\NestedNode\Tests\Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class NestedNodeRepositoryTest extends TestCase
{
    /**
     * @var \BackBee\Tests\Mock\MockBBApplication
     */
    private $application;

    /**
     * @var \BackBee\NestedNode\Tests\Mock\MockNestedNode
     */
    private $root_asc;

    /**
     * @var \BackBee\NestedNode\Tests\Mock\MockNestedNode
     */
    private $root_desc;

    /**
     * @var \BackBee\NestedNode\Repository\NestedNodeRepository
     */
    private $repo;

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::delete
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::shiftRlValues
     */
    public function testDelete()
    {
        $child = $this->repo->find('a-child1');

        $this->assertFalse($this->repo->delete($this->root_asc));
        $this->assertTrue($this->repo->delete($child));
        $this->application->getEntityManager()->refresh($this->root_asc);

        $this->assertEquals(1, $this->root_asc->getLeftnode());
        $this->assertEquals(4, $this->root_asc->getRightnode());
        $this->assertEquals(1, $this->root_desc->getLeftnode());
        $this->assertEquals(10, $this->root_desc->getRightnode());

        $this->application->getEntityManager()->clear();
        $this->assertNull($this->repo->find('a-child1'));
        $this->assertNull($this->repo->find('a-subchild1'));
        $this->assertNull($this->repo->find('a-subchild2'));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getAncestor
     */
    public function testGetAncestor()
    {
        $child = $this->repo->find('a-child1');
        $subchild = $this->repo->find('a-subchild1');

        $this->assertNull($this->repo->getAncestor($this->root_asc, 1));
        $this->assertEquals($this->root_asc, $this->repo->getAncestor($this->root_asc));

        $this->assertEquals($this->root_asc, $this->repo->getAncestor($child));
        $this->assertEquals($this->root_asc, $this->repo->getAncestor($child, 0));
        $this->assertEquals($child, $this->repo->getAncestor($child, 1));
        $this->assertNull($this->repo->getAncestor($child, 2));

        $this->assertEquals($this->root_asc, $this->repo->getAncestor($subchild));
        $this->assertEquals($child, $this->repo->getAncestor($subchild, 1));
        $this->assertEquals($subchild, $this->repo->getAncestor($subchild, 2));
        $this->assertNull($this->repo->getAncestor($subchild, 3));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getAncestors
     */
    public function testGetAncestors()
    {
        $child = $this->repo->find('a-child1');
        $subchild = $this->repo->find('a-subchild1');

        $this->assertEquals(array(), $this->repo->getAncestors($this->root_asc));
        $this->assertEquals(array($this->root_asc), $this->repo->getAncestors($this->root_asc, null, true));
        $this->assertEquals(array($this->root_asc), $this->repo->getAncestors($this->root_asc, 0, true));
        $this->assertEquals(array($this->root_asc), $this->repo->getAncestors($child));
        $this->assertEquals(array($this->root_asc, $child), $this->repo->getAncestors($child, null, true));
        $this->assertEquals(array($this->root_asc, $child), $this->repo->getAncestors($child, 1, true));
        $this->assertEquals(array($this->root_asc, $child), $this->repo->getAncestors($subchild));
        $this->assertEquals(array($this->root_asc, $child, $subchild), $this->repo->getAncestors($subchild, null, true));
        $this->assertEquals(array($this->root_asc, $child), $this->repo->getAncestors($subchild, 2));
        $this->assertEquals(array($child), $this->repo->getAncestors($subchild, 1));
        $this->assertEquals(array($this->root_asc, $child, $subchild), $this->repo->getAncestors($subchild, 2, true));
        $this->assertEquals(array($child), $this->repo->getAncestors($subchild, 1));
        $this->assertEquals(array($child, $subchild), $this->repo->getAncestors($subchild, 1, true));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getDescendants
     */
    public function testGetDescendants()
    {
        $child1 = $this->repo->find('a-child1');
        $child2 = $this->repo->find('a-child2');
        $subchild1 = $this->repo->find('a-subchild1');
        $subchild2 = $this->repo->find('a-subchild2');

        $this->assertEquals(array($child1, $subchild1, $subchild2, $child2), $this->repo->getDescendants($this->root_asc));
        $this->assertEquals(array($this->root_asc, $child1, $subchild1, $subchild2, $child2), $this->repo->getDescendants($this->root_asc, null, true));
        $this->assertEquals(array($this->root_asc, $child1, $child2), $this->repo->getDescendants($this->root_asc, 1, true));
        $this->assertEquals(array($subchild1, $subchild2), $this->repo->getDescendants($child1));
        $this->assertEquals(array(), $this->repo->getDescendants($child2));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::insertNodeAsFirstChildOf
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::shiftRlValues
     */
    public function testInsertNodeAsFirstChildOf()
    {
        $child1 = $this->repo->find('d-child1');
        $child2 = $this->repo->find('d-child2');
        $subchild1 = $this->repo->find('d-subchild1');
        $subchild2 = $this->repo->find('d-subchild2');

        $this->assertEquals(1, $this->root_desc->getLeftnode());
        $this->assertEquals(2, $child2->getLeftnode());
        $this->assertEquals(3, $child2->getRightnode());
        $this->assertEquals(4, $child1->getLeftnode());
        $this->assertEquals(5, $subchild2->getLeftnode());
        $this->assertEquals(6, $subchild2->getRightnode());
        $this->assertEquals(7, $subchild1->getLeftnode());
        $this->assertEquals(8, $subchild1->getRightnode());
        $this->assertEquals(9, $child1->getRightnode());
        $this->assertEquals(10, $this->root_desc->getRightnode());

        $this->assertEquals(0, $this->root_desc->getLevel());
        $this->assertEquals(1, $child1->getLevel());
        $this->assertEquals(1, $child2->getLevel());
        $this->assertEquals(2, $subchild1->getLevel());
        $this->assertEquals(2, $subchild2->getLevel());

        $this->assertEquals($this->root_desc, $child1->getRoot());
        $this->assertEquals($this->root_desc, $child2->getRoot());
        $this->assertEquals($this->root_desc, $subchild1->getRoot());
        $this->assertEquals($this->root_desc, $subchild2->getRoot());

        $this->assertEquals($this->root_desc, $child1->getParent());
        $this->assertEquals($this->root_desc, $child2->getParent());
        $this->assertEquals($child1, $subchild1->getParent());
        $this->assertEquals($child1, $subchild2->getParent());

        $this->assertEquals(1, $this->root_asc->getLeftnode());
        $this->assertEquals(10, $this->root_asc->getRightnode());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::_insertNode
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function test_insertNodeNotLeaf()
    {
        $child1 = $this->repo->find('d-child1');
        $child2 = $this->repo->find('d-child2');

        $this->repo->insertNodeAsFirstChildOf($child1, $child2);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::_insertNode
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function test_insertNodeSameAsParent()
    {
        $child1 = $this->repo->find('d-child1');

        $this->repo->insertNodeAsFirstChildOf($child1, $child1);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::_refreshExistingNode
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function test_refreshExistingNode()
    {
        $child1 = $this->repo->find('d-child1');
        $parent = new MockNestedNode('new-parent');

        $this->repo->insertNodeAsFirstChildOf($child1, $parent);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::insertNodeAsLastChildOf
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::shiftRlValues
     */
    public function testInsertNodeAsLastChildOf()
    {
        $child1 = $this->repo->find('a-child1');
        $child2 = $this->repo->find('a-child2');
        $subchild1 = $this->repo->find('a-subchild1');
        $subchild2 = $this->repo->find('a-subchild2');

        $this->assertEquals(1, $this->root_asc->getLeftnode());
        $this->assertEquals(2, $child1->getLeftnode());
        $this->assertEquals(3, $subchild1->getLeftnode());
        $this->assertEquals(4, $subchild1->getRightnode());
        $this->assertEquals(5, $subchild2->getLeftnode());
        $this->assertEquals(6, $subchild2->getRightnode());
        $this->assertEquals(7, $child1->getRightnode());
        $this->assertEquals(8, $child2->getLeftnode());
        $this->assertEquals(9, $child2->getRightnode());
        $this->assertEquals(10, $this->root_asc->getRightnode());

        $this->assertEquals(0, $this->root_asc->getLevel());
        $this->assertEquals(1, $child1->getLevel());
        $this->assertEquals(1, $child2->getLevel());
        $this->assertEquals(2, $subchild1->getLevel());
        $this->assertEquals(2, $subchild2->getLevel());

        $this->assertEquals($this->root_asc, $child1->getRoot());
        $this->assertEquals($this->root_asc, $child2->getRoot());
        $this->assertEquals($this->root_asc, $subchild1->getRoot());
        $this->assertEquals($this->root_asc, $subchild2->getRoot());

        $this->assertEquals($this->root_asc, $child1->getParent());
        $this->assertEquals($this->root_asc, $child2->getParent());
        $this->assertEquals($child1, $subchild1->getParent());
        $this->assertEquals($child1, $subchild2->getParent());

        $this->assertEquals(1, $this->root_desc->getLeftnode());
        $this->assertEquals(10, $this->root_desc->getRightnode());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getPrevSibling
     */
    public function testGetPrevSibling()
    {
        $child1 = $this->repo->find('a-child1');
        $child2 = $this->repo->find('a-child2');
        $subchild1 = $this->repo->find('a-subchild1');
        $subchild2 = $this->repo->find('a-subchild2');

        $this->assertNull($this->repo->getPrevSibling($this->root_desc));
        $this->assertNull($this->repo->getPrevSibling($child1));
        $this->assertNull($this->repo->getPrevSibling($subchild1));
        $this->assertEquals($subchild1, $this->repo->getPrevSibling($subchild2));
        $this->assertEquals($child1, $this->repo->getPrevSibling($child2));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getNextSibling
     */
    public function testGetNextSibling()
    {
        $child1 = $this->repo->find('a-child1');
        $child2 = $this->repo->find('a-child2');
        $subchild1 = $this->repo->find('a-subchild1');
        $subchild2 = $this->repo->find('a-subchild2');

        $this->assertNull($this->repo->getNextSibling($this->root_desc));
        $this->assertEquals($child2, $this->repo->getNextSibling($child1));
        $this->assertEquals($subchild2, $this->repo->getNextSibling($subchild1));
        $this->assertNull($this->repo->getNextSibling($subchild2));
        $this->assertNull($this->repo->getNextSibling($child2));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getSiblings
     */
    public function testGetSiblings()
    {
        $child1 = $this->repo->find('a-child1');
        $child2 = $this->repo->find('a-child2');
        $subchild1 = $this->repo->find('a-subchild1');
        $subchild2 = $this->repo->find('a-subchild2');
        $subchild3 = $this->repo->insertNodeAsLastChildOf(new MockNestedNode('a-subchild3'), $child1);
        $this->application->getEntityManager()->flush();

        $this->assertEquals(array(), $this->repo->getSiblings($this->root_asc));
        $this->assertEquals(array($this->root_asc), $this->repo->getSiblings($this->root_asc, true));
        $this->assertEquals(array($child2), $this->repo->getSiblings($child1));
        $this->assertEquals(array($subchild1, $subchild3), $this->repo->getSiblings($subchild2));
        $this->assertEquals(array($subchild1, $subchild2), $this->repo->getSiblings($subchild3));
        $this->assertEquals(array($subchild1, $subchild2, $subchild3), $this->repo->getSiblings($subchild1, true));
        $this->assertEquals(array($subchild3, $subchild2, $subchild1), $this->repo->getSiblings($subchild1, true, array('_leftnode' => 'desc')));
        $this->assertEquals(array($subchild3, $subchild2), $this->repo->getSiblings($subchild1, true, array('_leftnode' => 'desc'), 2));
        $this->assertEquals(array($subchild2, $subchild1), $this->repo->getSiblings($subchild1, true, array('_leftnode' => 'desc'), 2, 1));
        $this->assertEquals(array(), $this->repo->getSiblings($subchild1, true, array('_leftnode' => 'desc'), 2, 3));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getFirstChild
     */
    public function testGetFirstChild()
    {
        $child1 = $this->repo->find('a-child1');
        $child2 = $this->repo->find('a-child2');
        $subchild1 = $this->repo->find('a-subchild1');

        $this->assertEquals($child1, $this->repo->getFirstChild($this->root_asc));
        $this->assertEquals($subchild1, $this->repo->getFirstChild($child1));
        $this->assertNull($this->repo->getFirstChild($child2));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getLastChild
     */
    public function testGetLastChild()
    {
        $child1 = $this->repo->find('a-child1');
        $child2 = $this->repo->find('a-child2');
        $subchild2 = $this->repo->find('a-subchild2');

        $this->assertEquals($child2, $this->repo->getLastChild($this->root_asc));
        $this->assertEquals($subchild2, $this->repo->getLastChild($child1));
        $this->assertNull($this->repo->getFirstChild($child2));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::moveAsNextSiblingOf
     */
    public function testMoveAsNextSiblingOf()
    {
        $source = $this->repo->find('a-child1');
        $target = $this->repo->find('d-child2');

        $this->repo->moveAsNextSiblingOf($source, $target);
        $this->application->getEntityManager()->flush();
        $this->application->getEntityManager()->clear();

        $this->root_asc = $this->repo->find('a-root');
        $achild2 = $this->repo->find('a-child2');

        $this->assertEquals(1, $this->root_asc->getLeftnode());
        $this->assertEquals(2, $achild2->getLeftnode());
        $this->assertEquals(3, $achild2->getRightnode());
        $this->assertEquals(4, $this->root_asc->getRightnode());

        $this->root_desc = $this->repo->find('d-root');
        $achild1 = $this->repo->find('a-child1');
        $asubchild1 = $this->repo->find('a-subchild1');
        $asubchild2 = $this->repo->find('a-subchild2');
        $dchild1 = $this->repo->find('d-child1');
        $dchild2 = $this->repo->find('d-child2');

        $this->assertEquals(1, $this->root_desc->getLeftnode());
        $this->assertEquals(2, $dchild2->getLeftnode());
        $this->assertEquals(3, $dchild2->getRightnode());
        $this->assertEquals(4, $achild1->getLeftnode());
        $this->assertEquals(5, $asubchild1->getLeftnode());
        $this->assertEquals(6, $asubchild1->getRightnode());
        $this->assertEquals(7, $asubchild2->getLeftnode());
        $this->assertEquals(8, $asubchild2->getRightnode());
        $this->assertEquals(9, $achild1->getRightnode());
        $this->assertEquals(10, $dchild1->getLeftnode());
        $this->assertEquals(15, $dchild1->getRightnode());
        $this->assertEquals(16, $this->root_desc->getRightnode());
        $this->assertEquals($this->root_desc, $achild1->getParent());
        $this->assertEquals($this->root_desc, $achild1->getRoot());
        $this->assertEquals(1, $achild1->getLevel());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::moveAsPrevSiblingOf
     */
    public function testMoveAsPrevSiblingOf()
    {
        $source = $this->repo->find('d-child1');
        $target = $this->repo->find('a-subchild2');

        $this->repo->moveAsPrevSiblingOf($source, $target);
        $this->application->getEntityManager()->flush();
        $this->application->getEntityManager()->clear();

        $this->root_desc = $this->repo->find('d-root');
        $dchild2 = $this->repo->find('d-child2');

        $this->assertEquals(1, $this->root_desc->getLeftnode());
        $this->assertEquals(2, $dchild2->getLeftnode());
        $this->assertEquals(3, $dchild2->getRightnode());
        $this->assertEquals(4, $this->root_desc->getRightnode());

        $this->root_asc = $this->repo->find('a-root');
        $dchild1 = $this->repo->find('d-child1');
        $dsubchild1 = $this->repo->find('d-subchild1');
        $dsubchild2 = $this->repo->find('d-subchild2');
        $achild1 = $this->repo->find('a-child1');
        $asubchild1 = $this->repo->find('a-subchild1');
        $asubchild2 = $this->repo->find('a-subchild2');
        $achild2 = $this->repo->find('a-child2');

        $this->assertEquals(1, $this->root_asc->getLeftnode());
        $this->assertEquals(2, $achild1->getLeftnode());
        $this->assertEquals(3, $asubchild1->getLeftnode());
        $this->assertEquals(4, $asubchild1->getRightnode());
        $this->assertEquals(5, $dchild1->getLeftnode());
        $this->assertEquals(6, $dsubchild2->getLeftnode());
        $this->assertEquals(7, $dsubchild2->getRightnode());
        $this->assertEquals(8, $dsubchild1->getLeftnode());
        $this->assertEquals(9, $dsubchild1->getRightnode());
        $this->assertEquals(10, $dchild1->getRightnode());
        $this->assertEquals(11, $asubchild2->getLeftnode());
        $this->assertEquals(12, $asubchild2->getRightnode());
        $this->assertEquals(13, $achild1->getRightnode());
        $this->assertEquals(14, $achild2->getLeftnode());
        $this->assertEquals(15, $achild2->getRightnode());
        $this->assertEquals(16, $this->root_asc->getRightnode());
        $this->assertEquals($achild1, $dchild1->getParent());
        $this->assertEquals($this->root_asc, $dchild1->getRoot());
        $this->assertEquals(2, $dchild1->getLevel());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::moveAsFirstChildOf
     */
    public function testMoveAsFirstChildOf()
    {
        $source = $this->repo->find('a-subchild2');
        $this->repo->moveAsFirstChildOf($source, $this->root_asc);
        $this->application->getEntityManager()->flush();
        $this->application->getEntityManager()->clear();

        $this->root_asc = $this->repo->find('a-root');
        $achild1 = $this->repo->find('a-child1');
        $asubchild1 = $this->repo->find('a-subchild1');
        $asubchild2 = $this->repo->find('a-subchild2');
        $achild2 = $this->repo->find('a-child2');

        $this->assertEquals(1, $this->root_asc->getLeftnode());
        $this->assertEquals(2, $asubchild2->getLeftnode());
        $this->assertEquals(3, $asubchild2->getRightnode());
        $this->assertEquals(4, $achild1->getLeftnode());
        $this->assertEquals(5, $asubchild1->getLeftnode());
        $this->assertEquals(6, $asubchild1->getRightnode());
        $this->assertEquals(7, $achild1->getRightnode());
        $this->assertEquals(8, $achild2->getLeftnode());
        $this->assertEquals(9, $achild2->getRightnode());
        $this->assertEquals(10, $this->root_asc->getRightnode());
        $this->assertEquals($this->root_asc, $asubchild2->getParent());
        $this->assertEquals(1, $asubchild2->getLevel());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::moveAsLastChildOf
     */
    public function testMoveAsLastChildOf()
    {
        $source = $this->repo->find('d-child2');
        $target = $this->repo->find('d-child1');
        $this->repo->moveAsLastChildOf($source, $target);
        $this->application->getEntityManager()->flush();
        $this->application->getEntityManager()->clear();

        $this->root_desc = $this->repo->find('d-root');
        $dchild1 = $this->repo->find('d-child1');
        $dsubchild1 = $this->repo->find('d-subchild1');
        $dsubchild2 = $this->repo->find('d-subchild2');
        $dchild2 = $this->repo->find('d-child2');

        $this->assertEquals(1, $this->root_desc->getLeftnode());
        $this->assertEquals(2, $dchild1->getLeftnode());
        $this->assertEquals(3, $dsubchild2->getLeftnode());
        $this->assertEquals(4, $dsubchild2->getRightnode());
        $this->assertEquals(5, $dsubchild1->getLeftnode());
        $this->assertEquals(6, $dsubchild1->getRightnode());
        $this->assertEquals(7, $dchild2->getLeftnode());
        $this->assertEquals(8, $dchild2->getRightnode());
        $this->assertEquals(9, $dchild1->getRightnode());
        $this->assertEquals(10, $this->root_desc->getRightnode());
        $this->assertEquals($dchild1, $dchild2->getParent());
        $this->assertEquals(2, $dchild2->getLevel());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::moveAsNextSiblingOf
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testMoveAsNextSiblingOfDescendant()
    {
        $source = $this->repo->find('a-child1');
        $target = $this->repo->find('a-subchild1');
        $this->repo->moveAsNextSiblingOf($source, $target);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::moveAsNextSiblingOf
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testMoveAsNextSiblingOfIself()
    {
        $source = $this->repo->find('a-child1');
        $this->repo->moveAsNextSiblingOf($source, $source);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::moveAsNextSiblingOf
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testMoveAsNextSiblingOfRoot()
    {
        $source = $this->repo->find('a-child1');
        $this->repo->moveAsNextSiblingOf($source, $this->root_asc);
    }

    /**
     * Sets up the fixture
     */
    public function setUp()
    {
        $this->application = $this->getBBApp();
        $em = $this->application->getEntityManager();

        $st = new \Doctrine\ORM\Tools\SchemaTool($em);
        $st->createSchema(array($em->getClassMetaData('BackBee\NestedNode\Tests\Mock\MockNestedNode')));

        $this->root = new MockNestedNode('root');
        $em->persist($this->root);
        $em->flush($this->root);

        $this->_setRepo()
                ->_setRootAsc()
                ->_setRootDesc();
    }

    /**
     * Sets the NestedNode Repository
     * @return \BackBee\NestedNode\Tests\Repository\NestedNodeRepositoryTest
     */
    private function _setRepo()
    {
        $this->repo = $this->application
                ->getEntityManager()
                ->getRepository('BackBee\NestedNode\Tests\Mock\MockNestedNode');

        NestedNodeRepository::$config = array(
            'nestedNodeCalculateAsync' => false,
        );

        return $this;
    }

    /**
     * Initiate a new tree with node added as last child
     * @return \BackBee\NestedNode\Tests\Repository\NestedNodeRepositoryTest
     */
    private function _setRootAsc()
    {
        $this->root_asc = new MockNestedNode('a-root');

        $em = $this->application->getEntityManager();
        $em->persist($this->root_asc);
        $em->flush($this->root_asc);

        $child1 = $this->repo->insertNodeAsLastChildOf(new MockNestedNode('a-child1'), $this->root_asc);
        $em->flush($child1);

        $child2 = $this->repo->insertNodeAsLastChildOf(new MockNestedNode('a-child2'), $this->root_asc);
        $em->flush($child2);
        $em->refresh($child1);

        $subchild1 = $this->repo->insertNodeAsLastChildOf(new MockNestedNode('a-subchild1'), $child1);
        $em->flush($subchild1);

        $this->repo->insertNodeAsLastChildOf(new MockNestedNode('a-subchild2'), $child1);
        $em->flush();

        $em->refresh($this->root_asc);
        $em->refresh($child2);
        $em->refresh($subchild1);

        return $this;
    }

    /**
     * Initiate a new tree with node added as firt child
     * @return \BackBee\NestedNode\Tests\Repository\NestedNodeRepositoryTest
     */
    private function _setRootDesc()
    {
        $this->root_desc = new MockNestedNode('d-root');

        $em = $this->application->getEntityManager();
        $em->persist($this->root_desc);
        $em->flush($this->root_desc);

        $child1 = $this->repo->insertNodeAsFirstChildOf(new MockNestedNode('d-child1'), $this->root_desc);
        $em->flush($child1);

        $child2 = $this->repo->insertNodeAsFirstChildOf(new MockNestedNode('d-child2'), $this->root_desc);
        $em->flush($child2);
        $em->refresh($child1);

        $subchild1 = $this->repo->insertNodeAsFirstChildOf(new MockNestedNode('d-subchild1'), $child1);
        $em->flush($subchild1);

        $this->repo->insertNodeAsFirstChildOf(new MockNestedNode('d-subchild2'), $child1);
        $em->flush();

        $em->refresh($this->root_desc);
        $em->refresh($child2);
        $em->refresh($subchild1);

        return $this;
    }
}
