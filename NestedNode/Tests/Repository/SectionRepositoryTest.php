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

use BackBee\NestedNode\Section;
use BackBee\Site\Site;
use BackBee\Tests\BackBeeTestCase;

/**
 * @category    BackBee
 * 
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class SectionRepositoryTest extends BackBeeTestCase
{
    /**
     * @var \BackBee\NestedNode\Section
     */
    private $root;

    /**
     * @var \BackBee\NestedNode\Repository\SectionRepository
     */
    private $repository;

    /**
     * @covers \BackBee\NestedNode\Repository\SectionRepository::getRoot
     */
    public function testGetRoot()
    {
        $this->assertEquals($this->root, $this->repository->getRoot($this->root->getSite()));

        $new_site = new Site();
        $this->assertNull($this->repository->getRoot($new_site));
    }

    /**
     * @covers \BackBee\NestedNode\Repository\SectionRepository::updateTreeNatively
     */
    public function testUpdateTreeNatively()
    {
        $child1 = $this->repository->find('child1');
        $child2 = $this->repository->find('child2');

        $this->assertEquals(2, $child2->getLeftnode());
        $this->assertEquals(3, $child2->getRightnode());
        $this->assertEquals(1, $child2->getLevel());

        $this->assertEquals(4, $child1->getLeftnode());
        $this->assertEquals(5, $child1->getRightnode());
        $this->assertEquals(1, $child1->getLevel());

        $this->root->setLeftnode(rand(1, 20))
                ->setRightnode(rand(1, 20))
                ->setLevel(rand(0, 20));

        $child1->setLeftnode(rand(11, 20))
                ->setRightnode(rand(1, 20))
                ->setLevel(rand(0, 20));

        $child2->setLeftnode(rand(1, 10))
                ->setRightnode(rand(1, 20))
                ->setLevel(rand(0, 20));

        self::$em->flush();

        $expected = new \StdClass();
        $expected->uid = $this->root->getUid();
        $expected->leftnode = 1;
        $expected->rightnode = 6;
        $expected->level = 0;

        $this->assertEquals($expected, $this->repository->updateTreeNatively($this->root->getUid()));

        self::$em->refresh($child1);
        self::$em->refresh($child2);

        $this->assertEquals(2, $child2->getLeftnode());
        $this->assertEquals(3, $child2->getRightnode());
        $this->assertEquals(1, $child2->getLevel());

        $this->assertEquals(4, $child1->getLeftnode());
        $this->assertEquals(5, $child1->getRightnode());
        $this->assertEquals(1, $child1->getLevel());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\SectionRepository::getNativelyNodeChildren
     */
    public function testGetNativelyNodeChildren()
    {
        $this->assertEquals(array(), $this->repository->getNativelyNodeChildren('test'));
        $this->assertEquals(array('child2', 'child1'), $this->repository->getNativelyNodeChildren($this->root->getUid()));
    }

    public static function setUpBeforeClass()
    {
        self::$kernel->resetDatabase();
    }

    /**
     * Sets up the fixture
     */
    protected function setUp()
    {
        self::$em->clear();

        self::$kernel->resetDatabase([
            self::$em->getClassMetaData('BackBee\NestedNode\Section'),
            self::$em->getClassMetaData('BackBee\NestedNode\Page'),
            self::$em->getClassMetaData('BackBee\Site\Site'),
        ]);

        $this->repository = self::$em->getRepository('BackBee\NestedNode\Section');

        $site = new Site('site_uid', array('label' => 'site mock'));
        self::$em->persist($site);

        $this->root = new Section('root_uid', array('site' => $site));
        self::$em->persist($this->root);

        $child1 = $this->repository->insertNodeAsFirstChildOf(new Section('child1', array('site' => $site)), $this->root);
        self::$em->flush($child1);

        $child2 = $this->repository->insertNodeAsFirstChildOf(new Section('child2', array('site' => $site)), $this->root);
        self::$em->flush($child2);
        self::$em->refresh($child1);
        self::$em->refresh($this->root);
    }
}