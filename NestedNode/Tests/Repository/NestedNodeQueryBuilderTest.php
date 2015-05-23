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
class NestedNodeQueryBuilderTest extends BackBeeTestCase
{
    /**
     * @var \BackBee\NestedNode\Tests\Mock\MockNestedNode
     */
    private $root;

    /**
     * @var \BackBee\NestedNode\Repository\NestedNodeRepository
     */
    private $repository;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->repository = self::$em->getRepository('BackBee\NestedNode\Tests\Mock\MockNestedNode');
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

        $this->root = new MockNestedNode('root');
        self::$em->persist($this->root);
        self::$em->flush($this->root);

        $child1 = $this->repository->insertNodeAsLastChildOf(new MockNestedNode('child1'), $this->root);
        self::$em->persist($child1);
        self::$em->flush($child1);
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andIsNot
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::getFirstAlias
     */
    public function testAndIsNot()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andIsNot($this->root);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._uid != :uid0', $q->getDql());
        $this->assertEquals($this->root->getUid(), $q->getParameter('uid0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andRootIs
     */
    public function testAndRootIs()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andRootIs($this->root);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._root = :root0', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('root0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andParentIs
     */
    public function testAndParentIs()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andParentIs($this->root);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._parent = :parent0', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('parent0')->getValue());

        $qn = $this->repository->createQueryBuilder('n')
                ->andParentIs(null);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._parent IS NULL', $qn->getDql());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andLevelEquals
     */
    public function testAndLevelEquals()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andLevelEquals(5);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._level = :level0', $q->getDql());
        $this->assertEquals(5, $q->getParameter('level0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andLevelIsLowerThan
     */
    public function testAndLevelIsLowerThan()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andLevelIsLowerThan(5);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._level <= :level0', $q->getDql());
        $this->assertEquals(5, $q->getParameter('level0')->getValue());

        $qs = $this->repository->createQueryBuilder('n')
                ->andLevelIsLowerThan(5, true);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._level <= :level0', $qs->getDql());
        $this->assertEquals(4, $qs->getParameter('level0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andLevelIsUpperThan
     */
    public function testAndLevelIsUpperThan()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andLevelIsUpperThan(5);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._level >= :level0', $q->getDql());
        $this->assertEquals(5, $q->getParameter('level0')->getValue());

        $qs = $this->repository->createQueryBuilder('n')
                ->andLevelIsUpperThan(5, true);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._level >= :level0', $qs->getDql());
        $this->assertEquals(6, $qs->getParameter('level0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andLeftnodeEquals
     */
    public function testAndLeftnodeEquals()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andLeftnodeEquals(5);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._leftnode = :leftnode0', $q->getDql());
        $this->assertEquals(5, $q->getParameter('leftnode0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andLeftnodeIsLowerThan
     */
    public function testAndLeftnodeIsLowerThan()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andLeftnodeIsLowerThan(5);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._leftnode <= :leftnode0', $q->getDql());
        $this->assertEquals(5, $q->getParameter('leftnode0')->getValue());

        $qs = $this->repository->createQueryBuilder('n')
                ->andLeftnodeIsLowerThan(5, true);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._leftnode <= :leftnode0', $qs->getDql());
        $this->assertEquals(4, $qs->getParameter('leftnode0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andLeftnodeIsUpperThan
     */
    public function testAndLeftnodeIsUpperThan()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andLeftnodeIsUpperThan(5);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._leftnode >= :leftnode0', $q->getDql());
        $this->assertEquals(5, $q->getParameter('leftnode0')->getValue());

        $qs = $this->repository->createQueryBuilder('n')
                ->andLeftnodeIsUpperThan(5, true);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._leftnode >= :leftnode0', $qs->getDql());
        $this->assertEquals(6, $qs->getParameter('leftnode0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andRightnodeEquals
     */
    public function testAndRightnodeEquals()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andRightnodeEquals(5);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._rightnode = :rightnode0', $q->getDql());
        $this->assertEquals(5, $q->getParameter('rightnode0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andRightnodeIsLowerThan
     */
    public function testAndRightnodeIsLowerThan()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andRightnodeIsLowerThan(5);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._rightnode <= :rightnode0', $q->getDql());
        $this->assertEquals(5, $q->getParameter('rightnode0')->getValue());

        $qs = $this->repository->createQueryBuilder('n')
                ->andRightnodeIsLowerThan(5, true);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._rightnode <= :rightnode0', $qs->getDql());
        $this->assertEquals(4, $qs->getParameter('rightnode0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andRightnodeIsUpperThan
     */
    public function testAndRightnodeIsUpperThan()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andRightnodeIsUpperThan(5);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._rightnode >= :rightnode0', $q->getDql());
        $this->assertEquals(5, $q->getParameter('rightnode0')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andRightnodeIsUpperThan(5, true);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._rightnode >= :rightnode0', $q->getDql());
        $this->assertEquals(6, $q->getParameter('rightnode0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andIsSiblingsOf
     */
    public function testAndIsSiblingsOf()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->andIsSiblingsOf($this->root);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._uid = :uid0 AND n._parent IS NULL ORDER BY n._leftnode asc', $q->getDql());
        $this->assertEquals($this->root->getUid(), $q->getParameter('uid0')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsSiblingsOf($this->root, true);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._uid != :uid0 AND n._uid = :uid1 AND n._parent IS NULL ORDER BY n._leftnode asc', $q->getDql());
        $this->assertEquals($this->root->getUid(), $q->getParameter('uid0')->getValue());
        $this->assertEquals($this->root->getUid(), $q->getParameter('uid1')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsSiblingsOf($this->root, null, array('_rightnode' => 'desc'));
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._uid = :uid0 AND n._parent IS NULL ORDER BY n._rightnode desc', $q->getDql());
        $this->assertEquals($this->root->getUid(), $q->getParameter('uid0')->getValue());

        $child1 = $this->repository->find('child1');
        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsSiblingsOf($child1);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._parent = :parent0 ORDER BY n._leftnode asc', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('parent0')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsSiblingsOf($child1, true);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._uid != :uid0 AND n._parent = :parent1 ORDER BY n._leftnode asc', $q->getDql());
        $this->assertEquals($child1->getUid(), $q->getParameter('uid0')->getValue());
        $this->assertEquals($this->root, $q->getParameter('parent1')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsSiblingsOf($child1, true, array('_rightnode' => 'desc'), 10);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._uid != :uid0 AND n._parent = :parent1 ORDER BY n._rightnode desc', $q->getDql());
        $this->assertEquals($child1->getUid(), $q->getParameter('uid0')->getValue());
        $this->assertEquals($this->root, $q->getParameter('parent1')->getValue());
        $this->assertEquals(10, $q->getMaxResults());
        $this->assertEquals(0, $q->getFirstResult());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsSiblingsOf($child1, true, array('_rightnode' => 'desc'), 10, 5);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._uid != :uid0 AND n._parent = :parent1 ORDER BY n._rightnode desc', $q->getDql());
        $this->assertEquals($child1->getUid(), $q->getParameter('uid0')->getValue());
        $this->assertEquals($this->root, $q->getParameter('parent1')->getValue());
        $this->assertEquals(10, $q->getMaxResults());
        $this->assertEquals(5, $q->getFirstResult());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andIsPreviousSiblingOf
     */
    public function testAndIsPreviousSiblingOf()
    {
        $child1 = $this->repository->find('child1');
        $q = $this->repository->createQueryBuilder('n')
                ->andIsPreviousSiblingOf($child1);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._root = :root0 AND n._rightnode = :rightnode1', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('root0')->getValue());
        $this->assertEquals($child1->getLeftnode() - 1, $q->getParameter('rightnode1')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andIsPreviousSiblingsOf
     */
    public function testAndIsPreviousSiblingsOf()
    {
        $child1 = $this->repository->find('child1');
        $q = $this->repository->createQueryBuilder('n')
                ->andIsPreviousSiblingsOf($child1);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._parent = :parent0 AND n._leftnode <= :leftnode1', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('parent0')->getValue());
        $this->assertEquals($child1->getLeftnode() - 1, $q->getParameter('leftnode1')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andIsNextSiblingOf
     */
    public function testAndIsNextSiblingOf()
    {
        $child1 = $this->repository->find('child1');
        $q = $this->repository->createQueryBuilder('n')
                ->andIsNextSiblingOf($child1);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._root = :root0 AND n._leftnode = :leftnode1', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('root0')->getValue());
        $this->assertEquals($child1->getRightnode() + 1, $q->getParameter('leftnode1')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andIsNextSiblingsOf
     */
    public function testAndIsNextSiblingsOf()
    {
        $child1 = $this->repository->find('child1');
        $q = $this->repository->createQueryBuilder('n')
                ->andIsNextSiblingsOf($child1);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._parent = :parent0 AND n._leftnode >= :leftnode1', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('parent0')->getValue());
        $this->assertEquals($child1->getRightnode() + 1, $q->getParameter('leftnode1')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andIsAncestorOf
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::getAliasAndSuffix
     */
    public function testAndIsAncestorOf()
    {
        $child1 = $this->repository->find('child1');
        $q = $this->repository->createQueryBuilder('n')
                ->andIsAncestorOf($child1);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._root = :root0 AND n._leftnode <= :leftnode1 AND n._rightnode >= :rightnode2', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('root0')->getValue());
        $this->assertEquals($child1->getLeftnode(), $q->getParameter('leftnode1')->getValue());
        $this->assertEquals($child1->getRightnode(), $q->getParameter('rightnode2')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsAncestorOf($child1, true);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._root = :root0 AND n._leftnode <= :leftnode1 AND n._rightnode >= :rightnode2', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('root0')->getValue());
        $this->assertEquals($child1->getLeftnode() - 1, $q->getParameter('leftnode1')->getValue());
        $this->assertEquals($child1->getRightnode() + 1, $q->getParameter('rightnode2')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsAncestorOf($child1, true, 1);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._root = :root0 AND n._leftnode <= :leftnode1 AND n._rightnode >= :rightnode2 AND n._level = :level3', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('root0')->getValue());
        $this->assertEquals($child1->getLeftnode() - 1, $q->getParameter('leftnode1')->getValue());
        $this->assertEquals($child1->getRightnode() + 1, $q->getParameter('rightnode2')->getValue());
        $this->assertEquals(1, $q->getParameter('level3')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andIsDescendantOf
     */
    public function testAndIsDescendantOf()
    {
        $child1 = $this->repository->find('child1');
        $q = $this->repository->createQueryBuilder('n')
                ->andIsDescendantOf($child1);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._root = :root0 AND n._leftnode >= :leftnode1 AND n._rightnode <= :rightnode2', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('root0')->getValue());
        $this->assertEquals($child1->getLeftnode(), $q->getParameter('leftnode1')->getValue());
        $this->assertEquals($child1->getRightnode(), $q->getParameter('rightnode2')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsDescendantOf($child1, true);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._root = :root0 AND n._leftnode >= :leftnode1 AND n._rightnode <= :rightnode2', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('root0')->getValue());
        $this->assertEquals($child1->getLeftnode() + 1, $q->getParameter('leftnode1')->getValue());
        $this->assertEquals($child1->getRightnode() - 1, $q->getParameter('rightnode2')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsDescendantOf($child1, true, 1);

        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._root = :root0 AND n._leftnode >= :leftnode1 AND n._rightnode <= :rightnode2 AND n._level = :level3', $q->getDql());
        $this->assertEquals($this->root, $q->getParameter('root0')->getValue());
        $this->assertEquals($child1->getLeftnode() + 1, $q->getParameter('leftnode1')->getValue());
        $this->assertEquals($child1->getRightnode() - 1, $q->getParameter('rightnode2')->getValue());
        $this->assertEquals(1, $q->getParameter('level3')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::orderByMultiple
     */
    public function testOrderByMultiple()
    {
        $q = $this->repository->createQueryBuilder('n')
                ->orderByMultiple();
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n ORDER BY n._leftnode asc', $q->getDql());

        $q->orderByMultiple(array('_rightnode' => 'desc'));
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n ORDER BY n._rightnode desc', $q->getDql());

        $q->orderByMultiple(array('_leftnode' => 'asc', '_rightnode' => 'desc'));
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n ORDER BY n._leftnode asc, n._rightnode desc', $q->getDql());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andModifiedIsLowerThan
     */
    public function testAndModifiedIsLowerThan()
    {
        $now = new \DateTime();

        $q = $this->repository->createQueryBuilder('n')
                ->andModifiedIsLowerThan($now);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._modified < :date0', $q->getDql());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\NestedNodeQueryBuilder::andModifiedIsGreaterThan
     */
    public function testAndModifiedIsGreaterThan()
    {
        $now = new \DateTime();

        $q = $this->repository->createQueryBuilder('n')
                ->andModifiedIsGreaterThan($now);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\NestedNodeQueryBuilder', $q);
        $this->assertEquals('SELECT n FROM BackBee\NestedNode\Tests\Mock\MockNestedNode n WHERE n._modified > :date0', $q->getDql());
    }
}
