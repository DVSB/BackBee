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

use BackBee\NestedNode\Repository\NestedNodeRepository;
use BackBee\NestedNode\Tests\Mock\MockNestedNode;
use BackBee\Tests\BackBeeTestCase;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class NestedNodeRepositoryTest extends BackBeeTestCase
{
    /**
     * @var \BackBee\NestedNode\Repository\NestedNodeRepository
     */
    private $repository;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->repository = self::$em->getRepository('BackBee\NestedNode\Tests\Mock\MockNestedNode');
    }

    public static function setUpBeforeClass()
    {
        self::$kernel->resetDatabase();
    }

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        self::$em->clear();

        self::$kernel->resetDatabase([
            self::$em->getClassMetaData('BackBee\NestedNode\Tests\Mock\MockNestedNode'),
        ]);

        $this
            ->setRootAsc()
            ->setRootDesc()
        ;
    }

    /**
     * @var \BackBee\Tests\Mock\MockBBApplication
     */

    /**
     * @var \BackBee\NestedNode\Tests\Mock\MockNestedNode
     */
    private $root_asc;

    /**
     * @var \BackBee\NestedNode\Tests\Mock\MockNestedNode
     */
    private $root_desc;

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::delete
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::shiftRlValues
     */
    public function testDelete()
    {
        $child = $this->repository->find('a-child1');

        $this->assertFalse($this->repository->delete($this->root_asc));
        $this->assertTrue($this->repository->delete($child));
        self::$em->refresh($this->root_asc);

        $this->assertEquals(1, $this->root_asc->getLeftnode());
        $this->assertEquals(4, $this->root_asc->getRightnode());
        $this->assertEquals(1, $this->root_desc->getLeftnode());
        $this->assertEquals(10, $this->root_desc->getRightnode());

        self::$em->clear();
        $this->assertNull($this->repository->find('a-child1'));
        $this->assertNull($this->repository->find('a-subchild1'));
        $this->assertNull($this->repository->find('a-subchild2'));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getAncestor
     */
    public function testGetAncestor()
    {
        $child = $this->repository->find('a-child1');
        $subchild = $this->repository->find('a-subchild1');

        $this->assertNull($this->repository->getAncestor($this->root_asc, 1));
        $this->assertEquals($this->root_asc, $this->repository->getAncestor($this->root_asc));

        $this->assertEquals($this->root_asc, $this->repository->getAncestor($child));
        $this->assertEquals($this->root_asc, $this->repository->getAncestor($child, 0));
        $this->assertEquals($child, $this->repository->getAncestor($child, 1));
        $this->assertNull($this->repository->getAncestor($child, 2));

        $this->assertEquals($this->root_asc, $this->repository->getAncestor($subchild));
        $this->assertEquals($child, $this->repository->getAncestor($subchild, 1));
        $this->assertEquals($subchild, $this->repository->getAncestor($subchild, 2));
        $this->assertNull($this->repository->getAncestor($subchild, 3));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getAncestors
     */
    public function testGetAncestors()
    {
        $child = $this->repository->find('a-child1');
        $subchild = $this->repository->find('a-subchild1');

        $this->assertEquals(array(), $this->repository->getAncestors($this->root_asc));
        $this->assertEquals(array($this->root_asc), $this->repository->getAncestors($this->root_asc, null, true));
        $this->assertEquals(array($this->root_asc), $this->repository->getAncestors($this->root_asc, 0, true));
        $this->assertEquals(array($this->root_asc), $this->repository->getAncestors($child));
        $this->assertEquals(array($this->root_asc, $child), $this->repository->getAncestors($child, null, true));
        $this->assertEquals(array($this->root_asc, $child), $this->repository->getAncestors($child, 1, true));
        $this->assertEquals(array($this->root_asc, $child), $this->repository->getAncestors($subchild));
        $this->assertEquals(array($this->root_asc, $child, $subchild), $this->repository->getAncestors($subchild, null, true));
        $this->assertEquals(array($this->root_asc, $child), $this->repository->getAncestors($subchild, 2));
        $this->assertEquals(array($child), $this->repository->getAncestors($subchild, 1));
        $this->assertEquals(array($this->root_asc, $child, $subchild), $this->repository->getAncestors($subchild, 2, true));
        $this->assertEquals(array($child), $this->repository->getAncestors($subchild, 1));
        $this->assertEquals(array($child, $subchild), $this->repository->getAncestors($subchild, 1, true));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getDescendants
     */
    public function testGetDescendants()
    {
        $child1 = $this->repository->find('a-child1');
        $child2 = $this->repository->find('a-child2');
        $subchild1 = $this->repository->find('a-subchild1');
        $subchild2 = $this->repository->find('a-subchild2');

        $this->assertEquals(array($child1, $subchild1, $subchild2, $child2), $this->repository->getDescendants($this->root_asc));
        $this->assertEquals(array($this->root_asc, $child1, $subchild1, $subchild2, $child2), $this->repository->getDescendants($this->root_asc, null, true));
        $this->assertEquals(array($this->root_asc, $child1, $child2), $this->repository->getDescendants($this->root_asc, 1, true));
        $this->assertEquals(array($subchild1, $subchild2), $this->repository->getDescendants($child1));
        $this->assertEquals(array(), $this->repository->getDescendants($child2));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::insertNodeAsFirstChildOf
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::shiftRlValues
     */
    public function testInsertNodeAsFirstChildOf()
    {
        $child1 = $this->repository->find('d-child1');
        $child2 = $this->repository->find('d-child2');
        $subchild1 = $this->repository->find('d-subchild1');
        $subchild2 = $this->repository->find('d-subchild2');

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
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::insertNode
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function test_insertParentAsChild()
    {
        $child1 = $this->repository->find('d-child1');
        $child2 = $this->repository->find('d-subchild1');

        $this->repository->insertNodeAsFirstChildOf($child1, $child2);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::insertNode
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function test_insertNodeSameAsParent()
    {
        $child1 = $this->repository->find('d-child1');

        $this->repository->insertNodeAsFirstChildOf($child1, $child1);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::refreshExistingNode
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function test_refreshExistingNode()
    {
        $child1 = $this->repository->find('d-child1');
        $parent = new MockNestedNode('new-parent');

        $this->repository->insertNodeAsFirstChildOf($child1, $parent);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::insertNodeAsLastChildOf
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::shiftRlValues
     */
    public function testInsertNodeAsLastChildOf()
    {
        $child1 = $this->repository->find('a-child1');
        $child2 = $this->repository->find('a-child2');
        $subchild1 = $this->repository->find('a-subchild1');
        $subchild2 = $this->repository->find('a-subchild2');

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
        $child1 = $this->repository->find('a-child1');
        $child2 = $this->repository->find('a-child2');
        $subchild1 = $this->repository->find('a-subchild1');
        $subchild2 = $this->repository->find('a-subchild2');

        $this->assertNull($this->repository->getPrevSibling($this->root_desc));
        $this->assertNull($this->repository->getPrevSibling($child1));
        $this->assertNull($this->repository->getPrevSibling($subchild1));
        $this->assertEquals($subchild1, $this->repository->getPrevSibling($subchild2));
        $this->assertEquals($child1, $this->repository->getPrevSibling($child2));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getNextSibling
     */
    public function testGetNextSibling()
    {
        $child1 = $this->repository->find('a-child1');
        $child2 = $this->repository->find('a-child2');
        $subchild1 = $this->repository->find('a-subchild1');
        $subchild2 = $this->repository->find('a-subchild2');

        $this->assertNull($this->repository->getNextSibling($this->root_desc));
        $this->assertEquals($child2, $this->repository->getNextSibling($child1));
        $this->assertEquals($subchild2, $this->repository->getNextSibling($subchild1));
        $this->assertNull($this->repository->getNextSibling($subchild2));
        $this->assertNull($this->repository->getNextSibling($child2));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getSiblings
     */
    public function testGetSiblings()
    {
        $child1 = $this->repository->find('a-child1');
        $child2 = $this->repository->find('a-child2');
        $subchild1 = $this->repository->find('a-subchild1');
        $subchild2 = $this->repository->find('a-subchild2');
        $subchild3 = $this->repository->insertNodeAsLastChildOf(new MockNestedNode('a-subchild3'), $child1);
        self::$em->flush();

        $this->assertEquals(array(), $this->repository->getSiblings($this->root_asc));
        $this->assertEquals(array($this->root_asc), $this->repository->getSiblings($this->root_asc, true));
        $this->assertEquals(array($child2), $this->repository->getSiblings($child1));
        $this->assertEquals(array($subchild1, $subchild3), $this->repository->getSiblings($subchild2));
        $this->assertEquals(array($subchild1, $subchild2), $this->repository->getSiblings($subchild3));
        $this->assertEquals(array($subchild1, $subchild2, $subchild3), $this->repository->getSiblings($subchild1, true));
        $this->assertEquals(array($subchild3, $subchild2, $subchild1), $this->repository->getSiblings($subchild1, true, array('_leftnode' => 'desc')));
        $this->assertEquals(array($subchild3, $subchild2), $this->repository->getSiblings($subchild1, true, array('_leftnode' => 'desc'), 2));
        $this->assertEquals(array($subchild2, $subchild1), $this->repository->getSiblings($subchild1, true, array('_leftnode' => 'desc'), 2, 1));
        $this->assertEquals(array(), $this->repository->getSiblings($subchild1, true, array('_leftnode' => 'desc'), 2, 3));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getFirstChild
     */
    public function testGetFirstChild()
    {
        $child1 = $this->repository->find('a-child1');
        $child2 = $this->repository->find('a-child2');
        $subchild1 = $this->repository->find('a-subchild1');

        $this->assertEquals($child1, $this->repository->getFirstChild($this->root_asc));
        $this->assertEquals($subchild1, $this->repository->getFirstChild($child1));
        $this->assertNull($this->repository->getFirstChild($child2));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::getLastChild
     */
    public function testGetLastChild()
    {
        $child1 = $this->repository->find('a-child1');
        $child2 = $this->repository->find('a-child2');
        $subchild2 = $this->repository->find('a-subchild2');

        $this->assertEquals($child2, $this->repository->getLastChild($this->root_asc));
        $this->assertEquals($subchild2, $this->repository->getLastChild($child1));
        $this->assertNull($this->repository->getFirstChild($child2));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::moveAsNextSiblingOf
     */
    public function testMoveAsNextSiblingOf()
    {
        $source = $this->repository->find('a-child1');
        $target = $this->repository->find('d-child2');

        $this->repository->moveAsNextSiblingOf($source, $target);
        self::$em->flush();
        self::$em->clear();

        $this->root_asc = $this->repository->find('a-root');
        $achild2 = $this->repository->find('a-child2');

        $this->assertEquals(1, $this->root_asc->getLeftnode());
        $this->assertEquals(2, $achild2->getLeftnode());
        $this->assertEquals(3, $achild2->getRightnode());
        $this->assertEquals(4, $this->root_asc->getRightnode());

        $this->root_desc = $this->repository->find('d-root');
        $achild1 = $this->repository->find('a-child1');
        $asubchild1 = $this->repository->find('a-subchild1');
        $asubchild2 = $this->repository->find('a-subchild2');
        $dchild1 = $this->repository->find('d-child1');
        $dchild2 = $this->repository->find('d-child2');

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
        $source = $this->repository->find('d-child1');
        $target = $this->repository->find('a-subchild2');

        $this->repository->moveAsPrevSiblingOf($source, $target);
        self::$em->flush();
        self::$em->clear();

        $this->root_desc = $this->repository->find('d-root');
        $dchild2 = $this->repository->find('d-child2');

        $this->assertEquals(1, $this->root_desc->getLeftnode());
        $this->assertEquals(2, $dchild2->getLeftnode());
        $this->assertEquals(3, $dchild2->getRightnode());
        $this->assertEquals(4, $this->root_desc->getRightnode());

        $this->root_asc = $this->repository->find('a-root');
        $dchild1 = $this->repository->find('d-child1');
        $dsubchild1 = $this->repository->find('d-subchild1');
        $dsubchild2 = $this->repository->find('d-subchild2');
        $achild1 = $this->repository->find('a-child1');
        $asubchild1 = $this->repository->find('a-subchild1');
        $asubchild2 = $this->repository->find('a-subchild2');
        $achild2 = $this->repository->find('a-child2');

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
        $source = $this->repository->find('a-subchild2');
        $this->repository->moveAsFirstChildOf($source, $this->root_asc);
        self::$em->flush();
        self::$em->clear();

        $this->root_asc = $this->repository->find('a-root');
        $achild1 = $this->repository->find('a-child1');
        $asubchild1 = $this->repository->find('a-subchild1');
        $asubchild2 = $this->repository->find('a-subchild2');
        $achild2 = $this->repository->find('a-child2');

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
        $source = $this->repository->find('d-child2');
        $target = $this->repository->find('d-child1');
        $this->repository->moveAsLastChildOf($source, $target);
        self::$em->flush();
        self::$em->clear();

        $this->root_desc = $this->repository->find('d-root');
        $dchild1 = $this->repository->find('d-child1');
        $dsubchild1 = $this->repository->find('d-subchild1');
        $dsubchild2 = $this->repository->find('d-subchild2');
        $dchild2 = $this->repository->find('d-child2');

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
        $source = $this->repository->find('a-child1');
        $target = $this->repository->find('a-subchild1');
        $this->repository->moveAsNextSiblingOf($source, $target);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::moveAsNextSiblingOf
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testMoveAsNextSiblingOfIself()
    {
        $source = $this->repository->find('a-child1');
        $this->repository->moveAsNextSiblingOf($source, $source);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeRepository::moveAsNextSiblingOf
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testMoveAsNextSiblingOfRoot()
    {
        $source = $this->repository->find('a-child1');
        $this->repository->moveAsNextSiblingOf($source, $this->root_asc);
    }

    /**
     * Initiate a new tree with node added as last child.
     *
     * @return \BackBee\NestedNode\Tests\Repository\NestedNodeRepositoryTest
     */
    private function setRootAsc()
    {
        $this->root_asc = new MockNestedNode('a-root');

        self::$em->persist($this->root_asc);
        self::$em->flush($this->root_asc);

        $child1 = $this->repository->insertNodeAsLastChildOf(new MockNestedNode('a-child1'), $this->root_asc);
        self::$em->flush($child1);

        $child2 = $this->repository->insertNodeAsLastChildOf(new MockNestedNode('a-child2'), $this->root_asc);
        self::$em->flush($child2);
        self::$em->refresh($child1);

        $subchild1 = $this->repository->insertNodeAsLastChildOf(new MockNestedNode('a-subchild1'), $child1);
        self::$em->flush($subchild1);

        $this->repository->insertNodeAsLastChildOf(new MockNestedNode('a-subchild2'), $child1);
        self::$em->flush();

        self::$em->refresh($this->root_asc);
        self::$em->refresh($child2);
        self::$em->refresh($subchild1);

        return $this;
    }

    /**
     * Initiate a new tree with node added as firt child.
     *
     * @return \BackBee\NestedNode\Tests\Repository\NestedNodeRepositoryTest
     */
    private function setRootDesc()
    {
        $this->root_desc = new MockNestedNode('d-root');

        self::$em->persist($this->root_desc);
        self::$em->flush($this->root_desc);

        $child1 = $this->repository->insertNodeAsFirstChildOf(new MockNestedNode('d-child1'), $this->root_desc);
        self::$em->flush($child1);

        $child2 = $this->repository->insertNodeAsFirstChildOf(new MockNestedNode('d-child2'), $this->root_desc);
        self::$em->flush($child2);
        self::$em->refresh($child1);

        $subchild1 = $this->repository->insertNodeAsFirstChildOf(new MockNestedNode('d-subchild1'), $child1);
        self::$em->flush($subchild1);

        $this->repository->insertNodeAsFirstChildOf(new MockNestedNode('d-subchild2'), $child1);
        self::$em->flush();

        self::$em->refresh($this->root_desc);
        self::$em->refresh($child2);
        self::$em->refresh($subchild1);

        return $this;
    }
}
