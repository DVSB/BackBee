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

namespace BackBee\NestedNode\Tests;

use BackBee\NestedNode\KeyWord;
use BackBee\Tests\TestCase;

/**
 * @category    BackBee
 * @package     BackBee\NestedNode\Tests
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class KeyWordTest extends TestCase
{
    /**
     * @var \Datetime
     */
    private $current_time;

    /**
     * @var \BackBee\NestedNode\KeyWord
     */
    private $keyword;

    /**
     * @covers BackBee\NestedNode\MediaFolder::__construct
     */
    public function test__construct()
    {
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $this->keyword->getContent());
    }

    /**
     * @covers BackBee\NestedNode\MediaFolder::getKeyWord
     * @covers BackBee\NestedNode\MediaFolder::setKeyWord
     */
    public function testGetAndSetKeyWord()
    {
        $this->assertEquals($this->keyword, $this->keyword->setKeyWord('new keyword'));
        $this->assertEquals('new keyword', $this->keyword->getKeyWord());
    }

    /**
     * @covers BackBee\NestedNode\MediaFolder::getData
     */
    public function testGetData()
    {
        $this->assertEquals($this->keyword->toArray(), $this->keyword->getData());
        $this->assertNull($this->keyword->getData('fake'));
        $this->assertEquals('node_test', $this->keyword->getData('id'));
    }

    /**
     * @covers BackBee\NestedNode\MediaFolder::getParam
     */
    public function testGetParam()
    {
        $params = array(
            'left' => $this->keyword->getLeftnode(),
            'right' => $this->keyword->getRightnode(),
            'level' => $this->keyword->getLevel(),
        );

        $this->assertEquals($params, $this->keyword->getParam());
        $this->assertNull($this->keyword->getParam('fake'));

        $this->assertEquals($this->keyword->getLeftnode(), $this->keyword->getParam('left'));
    }

    /**
     * @covers BackBee\NestedNode\MediaFolder::toArray
     */
    public function testToArray()
    {
        $expected = array(
            'id' => 'node_test',
            'rel' => 'leaf',
            'uid' => 'test',
            'rootuid' => 'test',
            'parentuid' => null,
            'created' => $this->current_time->getTimestamp(),
            'modified' => $this->current_time->getTimestamp(),
            'isleaf' => true,
            'keyword' => 'test',
        );

        $this->assertEquals($expected, $this->keyword->toArray());
    }

    /**
     * @covers BackBee\NestedNode\MediaFolder::toStdObject
     */
    public function testToStdObject()
    {
        $obj = $this->keyword->toStdObject();
        $this->assertEquals('test', $obj->uid);
        $this->assertEquals(0, $obj->level);
        $this->assertEquals('test', $obj->keyword);
        $this->assertEquals(array(), $obj->children);
    }

    /**
     * Sets up the fixture
     */
    public function setUp()
    {
        $this->current_time = new \Datetime();
        $this->keyword = new KeyWord('test');
        $this->keyword->setKeyWord('test');
    }
}
