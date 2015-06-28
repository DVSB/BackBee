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

namespace BackBee\NestedNode\Tests;

use BackBee\NestedNode\Page;
use BackBee\NestedNode\Section;
use BackBee\Site\Site;
use BackBee\Tests\BackBeeTestCase;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class SectionTest extends BackBeeTestCase
{

    /**
     * @covers BackBee\NestedNode\Section::__construct
     */
    public function test__construct()
    {
        $section = new Section();

        $this->assertNotNull($section->getPage());
        $this->assertNull($section->getSite());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $section->getPages());
    }

    /**
     * @covers BackBee\NestedNode\Section::__construct
     */
    public function test__constructWithOptions()
    {
        $page = new Page();
        $site = new Site();
        $section = new Section(null, array('page' => $page, 'site' => $site));

        $this->assertEquals($page, $section->getPage());
        $this->assertEquals($site, $section->getSite());
    }

    /**
     * @covers BackBee\NestedNode\Section::__clone
     */
    public function test__clone()
    {
        $page = new Page();
        $root = new Section('root');

        $child = new Section('child');
        $child->setRoot($root)
                ->setParent($root)
                ->setLeftnode(2)
                ->setRightnode(3)
                ->setLevel(1);

        $root->getChildren()->add($child);
        $root->getDescendants()->add($child);
        $root->getPages()->add($page);

        $clone = clone $child;
        $this->assertNotEquals($child->getUid(), $clone->getUid());
        $this->assertEquals(1, $clone->getLeftnode());
        $this->assertEquals(2, $clone->getRightnode());
        $this->assertEquals(0, $clone->getLevel());
        $this->assertNull($clone->getParent());
        $this->assertEquals($clone, $clone->getRoot());
        $this->assertEquals(0, $clone->getChildren()->count());
        $this->assertEquals(0, $clone->getDescendants()->count());
        $this->assertEquals(0, $clone->getPages()->count());
    }

    /**
     * @covers BackBee\NestedNode\Section::setPage
     */
    public function testSetPage()
    {
        $section = new Section();
        $page = new Page();

        $this->assertEquals($section, $section->setPage($page));
        $this->assertEquals($page, $section->getPage());
    }

    /**
     * @covers BackBee\NestedNode\Section::setSite
     */
    public function testSetSite()
    {
        $section = new Section();
        $site = new Site();

        $this->assertEquals($section, $section->setSite($site));
        $this->assertEquals($site, $section->getSite());
        $this->assertEquals($section, $section->setSite(null));
        $this->assertNull($section->getSite());
    }

    /**
     * @covers BackBee\NestedNode\Section::isLeaf
     */
    public function testIsLeaf()
    {
        $section = new Section();
        $this->assertFalse($section->isLeaf());
    }

    /**
     * Test cascade Doctrine annotations for entity
     */
    public function testDoctrineCascade()
    {
        self::$kernel->resetDatabase();
        $site = new Site('site-test', ['label' => 'site-test']);
        $layout = self::$kernel->createLayout('layout-test', 'layout-test');
        self::$em->persist($site);
        self::$em->persist($layout);
        self::$em->flush();

        $root = new Section('root');
        $root->setSite($site)
                ->getPage()
                ->setLayout($layout);

        // Persist cascade on Section::_page
        self::$em->persist($root);
        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForInsert($root));
        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForInsert($root->getPage()));
        self::$em->flush($root);

        // Remove cascade on Section::_page
        self::$em->remove($root);
        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForDelete($root));
        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForDelete($root->getPage()));
        self::$em->flush();
    }

}
