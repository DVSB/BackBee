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

namespace BackBee\ClassContent\Tests;

use BackBee\ClassContent\AClassContent;
use BackBee\ClassContent\Element\Image;
use BackBee\ClassContent\Revision;
use BackBee\ClassContent\Tests\Mock\MockContent;
use BackBee\Exception\BBException;

/**
 * @category    BackBee
 * @package     BackBee\NestedNode\Tests
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 *              e.chau <eric.chau@lp-digital.fr>
 */
class ClassContentTest extends \PHPUnit_Framework_TestCase
{
    private $content;

    public function setUp()
    {
        $this->content = new MockContent();
        $this->content->load();
    }

    /**
     * test getProperty
     *
     * @coverage \BackBee\ClassContent\AClassContent::getProperty
     */
    public function testGetProperty()
    {
        $this->assertInternalType('array', $this->content->getProperty());
        $this->assertEquals('Mock Content', $this->content->getProperty('name'));
        $this->assertNull($this->content->getProperty('notset'));
    }

    /**
     * test setProperty
     *
     * @coverage \BackBee\ClassContent\AClassContent::setProperty
     */
    public function testSetProperty()
    {
        $this->content->setProperty('foo', 'bar');
        $this->assertEquals('bar', $this->content->getProperty('foo'));
    }

    /**
     * test createClone
     *
     * @coverage \BackBee\ClassContent\AClassContent::createClone
     */
    public function testCreateClone()
    {
        $this->content->setProperty('foo', 'bar');
        $this->content->title->value = 'baz';
        $clone = $this->content->createClone();

        $this->assertInstanceOf('BackBee\ClassContent\Tests\Mock\MockContent', $clone);
        $this->assertNull($clone->getProperty('foo'));
        $this->assertEquals('baz', $clone->title->value);
        $this->assertNotEquals($this->content->getUid(), $clone->getUid());
    }

    /**
     * test setProperty
     */
    public function testAcceptedType()
    {
        $this->assertTrue($this->content->isAccepted($this->content->title, 'title'));
        $this->assertTrue($this->content->isAccepted('foo', 'bar'));

        $this->assertFalse($this->content->isAccepted(new \stdClass(), 'title'));
        $this->assertFalse($this->content->isAccepted('false'));
    }

    public function testDefineProperty()
    {
        $name = $this->content->getProperty('name');

        $this->content->mockedDefineProperty('name', $name.' foobar');
        $this->assertEquals($name, $this->content->getProperty('name'));

        $this->content->mockedDefineProperty('newproperty', 'foobar');
        $this->assertEquals('foobar', $this->content->getProperty('newproperty'));
    }

    public function testDefineParam()
    {
        $defaultParams = $this->content->getDefaultParams();

        $this->assertFalse(isset($defaultParams['test_foobar']));
        $this->content->mockedDefineParam('test_foobar', [
            'default' => 'hello world',
            'value'   => null,
        ]);

        $defaultParams = $this->content->getDefaultParams();
        $this->assertTrue(isset($defaultParams['test_foobar']));
        $this->assertTrue(array_key_exists('value', $defaultParams['test_foobar']));
        $this->assertNull($defaultParams['test_foobar']['value']);
        $this->assertEquals('hello world', $defaultParams['test_foobar']['default']);
    }

    public function testSetAndGetParam()
    {
        $content = new Image();
        $defaultParams = $content->getDefaultParams();

        foreach ($defaultParams as $param) {
            $this->assertTrue(array_key_exists('value', $param));
        }

        foreach ($content->getAllParams() as $param) {
            $this->assertTrue(array_key_exists('value', $param));
        }

        $this->assertSame($defaultParams, $content->getAllParams());
        $this->assertNull($content->getParam('foobar'));
        $this->assertEquals($defaultParams['width'], $content->getParam('width'));
        $this->assertSame(50, $content->getParamValue('width'));

        $content->setParam('width', '1234');
        $this->assertNotSame(1234, $content->getParamValue('width'));
        $this->assertSame('1234', $content->getParamValue('width'));
        $this->assertNotEquals($defaultParams, $content->getAllParams());
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Cannot set foo as parameter cause this key does not exist.
     */
    public function testSetInvalidParam()
    {
        $this->content->setParam('foo', 'bar');
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Cannot replace width's value, integer expected and boolean given.
     */
    public function testSetIncompatibleParam()
    {
        (new Image())->setParam('width', false);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Parameter's value cannot be type of object.
     */
    public function testSetInvalidParamType()
    {
        (new Image())->setParam('width', new \stdClass());
    }

    public function testDefineData()
    {
        $this->content->mockedDefineData(
            'title',
            '\BackBee\ClassContent\Element\Date',
            array(
                'default' => array('value' => 'Foo Bar Baz'),
            )
        );
        $this->content->mockedDefineData(
            'title',
            '\BackBee\ClassContent\Element\Image',
            array(
                'default' => array('value' => 'Foo Bar Baz'),
            ),
            false
        );
        $this->content->mockedDefineData(
            'date',
            '\BackBee\ClassContent\Element\Date',
            array(
                'default' => array('value' => 'A date'),
            ),
            true
        );

        $this->assertNotEquals('Foo Bar Baz', $this->content->title->value);
        $this->assertInstanceOf('BackBee\ClassContent\Element\Date', $this->content->date);

        $this->assertTrue($this->content->isAccepted($this->content->date, 'title'));
        $this->assertTrue($this->content->isAccepted($this->content->title, 'title'));
        try {

        $this->assertFalse($this->content->isAccepted(new Image(), 'title'));
    } catch (\Exception $e) {
        var_dump($e->getMessage(), get_class($e));
    }

        $this->assertEquals('A date', $this->content->date->value);
    }

    public function testJsonSerialize()
    {
        $this->assertInstanceOf('JsonSerializable', $this->content);

        $data = $this->content->JsonSerialize();

        $this->assertTrue(is_array($data));
        $this->assertTrue(isset($data['uid']));
        $this->assertTrue(isset($data['label']));
        $this->assertTrue(isset($data['type']));
        $this->assertTrue(isset($data['state']));
        $this->assertTrue(isset($data['created']));
        $this->assertTrue(isset($data['modified']));
        $this->assertTrue(isset($data['revision']));
        $this->assertTrue(isset($data['parameters']));
        $this->assertTrue(isset($data['accept']));
        $this->assertTrue(isset($data['minentry']));
        $this->assertTrue(isset($data['maxentry']));
        $this->assertTrue(isset($data['elements']));
        $this->assertTrue(isset($data['properties']));
        $this->assertTrue(isset($data['image']));

        $this->assertTrue(is_array($data['elements']));

        foreach ($data['elements'] as $key => $values) {
            if (is_object($this->content->$key)) {
                $this->assertTrue(is_array($values));
                $this->assertTrue(isset($values['type']));
                $this->assertTrue(isset($values['uid']));
            } elseif (is_scalar($this->content->$key)) {
                $this->assertTrue(is_scalar($values));
            }
        }

        $this->assertEquals('foobar', $data['image']);
    }

    public function testJsonSerializeDefinitionFormat()
    {
        $data = $this->content->JsonSerialize(AClassContent::JSON_DEFINITION_FORMAT);

        $this->assertTrue(isset($data['label']));
        $this->assertTrue(isset($data['type']));
        $this->assertTrue(isset($data['parameters']));
        $this->assertTrue(isset($data['accept']));
        $this->assertTrue(isset($data['minentry']));
        $this->assertTrue(isset($data['maxentry']));
        $this->assertTrue(isset($data['properties']));
        $this->assertTrue(isset($data['image']));

        $this->assertFalse(isset($data['state']));
        $this->assertFalse(isset($data['created']));
        $this->assertFalse(isset($data['modified']));
        $this->assertFalse(isset($data['revision']));
        $this->assertFalse(isset($data['elements']));
        $this->assertFalse(isset($data['uid']));

        $this->assertEquals($this->content->getDefaultImageName(), $data['image']);
    }

    public function testJsonSerializeConciseFormat()
    {
        $data = $this->content->JsonSerialize(AClassContent::JSON_CONCISE_FORMAT);

        $this->assertTrue(isset($data['uid']));
        $this->assertTrue(isset($data['type']));
        $this->assertTrue(isset($data['label']));
        $this->assertTrue(isset($data['parameters']));
        $this->assertTrue(isset($data['elements']));
        $this->assertTrue(isset($data['image']));

        $this->assertFalse(isset($data['state']));
        $this->assertFalse(isset($data['accept']));
        $this->assertFalse(isset($data['created']));
        $this->assertFalse(isset($data['modified']));
        $this->assertFalse(isset($data['revision']));
        $this->assertFalse(isset($data['minentry']));
        $this->assertFalse(isset($data['maxentry']));
        $this->assertFalse(isset($data['properties']));

        $this->assertEquals('foobar', $data['image']);
    }

    public function testJsonSerializeInfoFormat()
    {
        $data = $this->content->JsonSerialize(AClassContent::JSON_INFO_FORMAT);

        $this->assertTrue(isset($data['uid']));
        $this->assertTrue(isset($data['type']));
        $this->assertTrue(isset($data['state']));
        $this->assertTrue(isset($data['created']));
        $this->assertTrue(isset($data['modified']));
        $this->assertTrue(isset($data['revision']));

        $this->assertFalse(isset($data['label']));
        $this->assertFalse(isset($data['parameters']));
        $this->assertFalse(isset($data['elements']));
        $this->assertFalse(isset($data['image']));
        $this->assertFalse(isset($data['accept']));
        $this->assertFalse(isset($data['minentry']));
        $this->assertFalse(isset($data['maxentry']));
        $this->assertFalse(isset($data['properties']));
    }
}
