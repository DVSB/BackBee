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

namespace BackBuilder\NestedNode\Tests;

use BackBuilder\NestedNode\Tests\Mock\MockNestedNode;

/**
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Tests
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class ANestedNodeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Datetime
     */
    protected $current_time;

    /**
     * @var \BackBuilder\NestedNode\Tests\Mock\MockNestedNode 
     */
    private $mock;

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::__construct
     */
    public function test__construct()
    {
        $mock = new MockNestedNode();

        $this->assertNotEmpty($mock->getUid());
        $this->assertEquals(1, $mock->getLeftnode());
        $this->assertEquals(2, $mock->getRightnode());
        $this->assertEquals(0, $mock->getLevel());
        $this->assertNotEmpty($mock->getCreated());
        $this->assertEquals($mock->getCreated(), $mock->getModified());
        $this->assertEquals($mock, $mock->getRoot());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $mock->getChildren());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $mock->getDescendants());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::getUid
     */
    public function testGetUid()
    {
        $this->assertEquals('test', $this->mock->getUid());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::getRoot
     */
    public function testGetRoot()
    {
        $this->assertEquals($this->mock, $this->mock->getRoot());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::getParent
     */
    public function testGetParent()
    {
        $this->assertNull($this->mock->getParent());

        $this->mock->setParent($this->mock);
        $this->assertEquals($this->mock, $this->mock->getParent());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::getLeftnode
     */
    public function testGetLeftnode()
    {
        $this->assertEquals(1, $this->mock->getLeftnode());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::getRightnode
     */
    public function testGetRightnode()
    {
        $this->assertEquals(2, $this->mock->getRightnode());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::getWeight
     */
    public function testGetWeight()
    {
        $this->assertEquals(2, $this->mock->getWeight());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::getCreated
     */
    public function testGetCreated()
    {
        $this->assertEquals($this->current_time, $this->mock->getCreated());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::getModified
     */
    public function testGetModified()
    {
        $this->assertEquals($this->current_time, $this->mock->getModified());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::getDescendants
     */
    public function testGetDescendants()
    {
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $this->mock->getDescendants());
        $this->assertEquals(0, $this->mock->getDescendants()->count());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::getChildren
     */
    public function testGetChildren()
    {
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $this->mock->getChildren());
        $this->assertEquals(0, $this->mock->getChildren()->count());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::isRoot
     */
    public function testIsRoot()
    {
        $this->assertTrue($this->mock->isRoot());

        $this->mock->setLeftnode(2);
        $this->assertFalse($this->mock->isRoot());

        $this->mock->setParent($this->mock);
        $this->assertFalse($this->mock->isRoot());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::isLeaf
     */
    public function testIsLeaf()
    {
        $this->assertTrue($this->mock->isLeaf());

        $this->mock->setRightnode(4);
        $this->assertFalse($this->mock->isLeaf());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::isAncestorOf
     */
    public function testIsAncestorOf()
    {
        $this->assertFalse($this->mock->isAncestorOf($this->mock));
        $this->assertTrue($this->mock->isAncestorOf($this->mock, false));

        $other_mock = new MockNestedNode();
        $this->assertFalse($this->mock->isAncestorOf($other_mock));

        $other_mock->setRoot($this->mock)
                ->setLeftnode(2)
                ->setRightnode(3);
        $this->mock->setRightnode(4);
        $this->assertTrue($this->mock->isAncestorOf($other_mock));
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::isDescendantOf
     */
    public function testIsDescendantOf()
    {
        $this->assertFalse($this->mock->isDescendantOf($this->mock));
        $this->assertTrue($this->mock->isDescendantOf($this->mock, false));

        $other_mock = new MockNestedNode();
        $this->assertFalse($other_mock->isDescendantOf($this->mock));

        $other_mock->setRoot($this->mock)
                ->setLeftnode(2)
                ->setRightnode(3);
        $this->mock->setRightnode(4);
        $this->assertTrue($other_mock->isDescendantOf($this->mock));
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::setUid
     */
    public function testSetUid()
    {
        $this->assertEquals($this->mock, $this->mock->setUid('new-uid'));
        $this->assertEquals('new-uid', $this->mock->getUid());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::setRoot
     */
    public function testSetRoot()
    {
        $new_root = new MockNestedNode();
        $this->assertEquals($this->mock, $this->mock->setRoot($new_root));
        $this->assertEquals($new_root, $this->mock->getRoot());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::setParent
     */
    public function testSetParent()
    {
        $new_mock = new MockNestedNode();
        $this->assertEquals($this->mock, $this->mock->setParent($new_mock));
        $this->assertEquals($new_mock, $this->mock->getParent());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::setLeftnode
     */
    public function testSetLeftnode()
    {
        $this->assertEquals($this->mock, $this->mock->setLeftnode(2));
        $this->assertEquals(2, $this->mock->getLeftnode());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::setLeftnode
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testSetLeftnodeWithNonNumeric()
    {
        $this->mock->setLeftnode('a');
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::setRightnode
     */
    public function testSetRightnode()
    {
        $this->assertEquals($this->mock, $this->mock->setRightnode(5));
        $this->assertEquals(5, $this->mock->getRightnode());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::setRightnode
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testSetRightnodeWithNonNumeric()
    {
        $this->mock->setRightnode('a');
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::setLevel
     */
    public function testSetLevel()
    {
        $this->assertEquals($this->mock, $this->mock->setLevel(5));
        $this->assertEquals(5, $this->mock->getLevel());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::setLevel
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testSetLevelWithNonNumeric()
    {
        $this->mock->setLevel('a');
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::setCreated
     */
    public function testSetCreated()
    {
        $now = new \Datetime();
        $this->assertEquals($this->mock, $this->mock->setCreated($now));
        $this->assertEquals($now, $this->mock->getCreated());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::setModified
     */
    public function testSetModified()
    {
        $now = new \Datetime();
        $this->assertEquals($this->mock, $this->mock->setModified($now));
        $this->assertEquals($now, $this->mock->getModified());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::toArray
     */
    public function testToArray()
    {
        $now = new \Datetime();
        $child = new MockNestedNode('child');
        $child->setRoot($this->mock)
                ->setParent($this->mock)
                ->setLeftnode(2)
                ->setRightnode(3);
        $this->mock->setRightnode(4);

        $expected_mock = array(
            'id' => 'node_test',
            'rel' => 'folder',
            'uid' => 'test',
            'rootuid' => 'test',
            'parentuid' => null,
            'created' => $this->current_time->getTimestamp(),
            'modified' => $this->current_time->getTimestamp(),
            'isleaf' => false
        );

        $expected_child = array(
            'id' => 'node_child',
            'rel' => 'leaf',
            'uid' => 'child',
            'rootuid' => 'test',
            'parentuid' => 'test',
            'created' => $now->getTimestamp(),
            'modified' => $now->getTimestamp(),
            'isleaf' => true
        );

        $this->assertEquals($expected_mock, $this->mock->toArray());
        $this->assertEquals($expected_child, $child->toArray());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::serialize
     */
    public function testSerialize()
    {
        $expected = '{"id":"node_test","rel":"leaf","uid":"test","rootuid":"test","parentuid":null,"created":' . $this->current_time->getTimestamp() . ',"modified":' . $this->current_time->getTimestamp() . ',"isleaf":true}';
        $this->assertEquals($expected, $this->mock->serialize());
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::serialize
     */
    public function testUnserialize()
    {
        $serialized = '{"id":"node_test","rel":"leaf","uid":"test","rootuid":"test","parentuid":null,"created":' . $this->current_time->getTimestamp() . ',"modified":' . $this->current_time->getTimestamp() . ',"isleaf":true}';
        $new_from_string = new MockNestedNode();
        $this->assertEquals($this->mock, $new_from_string->unserialize($serialized));

        $sdtClass = json_decode($serialized);
        $new_from_object = new MockNestedNode();
        $this->assertEquals($this->mock, $new_from_object->unserialize($sdtClass));

        $sdtClass->unknown = 'unknown';
        $new_from_too_large_object = new MockNestedNode();
        $this->assertEquals($this->mock, $new_from_too_large_object->unserialize($sdtClass));
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::serialize
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testUnserializeWithInvalidSerialized()
    {
        $serialized = '';
        $new = new MockNestedNode();
        $new->unserialize($serialized);
    }

    /**
     * @covers BackBuilder\NestedNode\ANestedNode::serialize
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testUnserializeWithStrictOptionActivated()
    {
        $serialized = '{"unknown":"unknown","id":"node_test","rel":"leaf","uid":"test","rootuid":"test","parentuid":null,"created":' . $this->current_time->getTimestamp() . ',"modified":' . $this->current_time->getTimestamp() . ',"isleaf":true}';
        $new = new MockNestedNode();
        $new->unserialize($serialized, true);
    }

    /**
     * Sets up the fixture
     */
    public function setUp()
    {
        $this->current_time = new \Datetime();
        $this->mock = new MockNestedNode('test');
    }

}
