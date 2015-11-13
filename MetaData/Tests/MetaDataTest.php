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

use BackBee\MetaData\MetaData;
use BackBee\Tests\BackBeeTestCase;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MetaDataTest extends BackBeeTestCase
{

    /**
     * @covers BackBee\MetaData\MetaData::__construct
     */
    public function test__construct()
    {
        $metaWithoutName = new MetaData();
        $this->assertNull($metaWithoutName->getName());

        $metaWithName = new MetaData('test');
        $this->assertEquals('test', $metaWithName->getName());
    }

    /**
     * @covers BackBee\MetaData\MetaData::setName
     *
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Invalid name for metadata: ` `.
     */
    public function testInvalidName()
    {
        $meta = new MetaData();
        $meta->setName(' ');
    }

    /**
     * @covers BackBee\MetaData\MetaData::hasAttribute
     */
    public function testHasAttribute()
    {
        $meta = new MetaData();
        $this->assertFalse($meta->hasAttribute('test'));

        $meta->setAttribute('test', 'test');
        $this->assertTrue($meta->hasAttribute('test'));
    }

    /**
     * @covers BackBee\MetaData\MetaData::getAttribute
     */
    public function testGetAttribute()
    {
        $meta = new MetaData();
        $this->assertEquals('', $meta->getAttribute('test'));
        $this->assertEquals('default', $meta->getAttribute('test', 'default'));

        $meta->setAttribute('test', 'test');
        $this->assertEquals('test', $meta->getAttribute('test', 'default'));
    }

    /**
     * @covers BackBee\MetaData\MetaData::isComputed
     */
    public function testIsComputed()
    {
        $meta = new MetaData();
        $this->assertTrue($meta->isComputed('test'));

        $meta->setAttribute('test', 'test');
        $this->assertFalse($meta->isComputed('test'));

        $meta->setAttribute('test', 'test', null, true);
        $this->assertTrue($meta->isComputed('test'));

        $meta->setAttribute('test', 'test', null, 'true');
        $this->assertFalse($meta->isComputed('test'));
    }

    /**
     * @covers BackBee\MetaData\MetaData::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $meta = new MetaData();
        $meta->setAttribute('name', 'name value');
        $meta->setAttribute('content', 'content value');

        $expected = [
            ['attr' => 'name', 'value' => 'name value'],
            ['attr' => 'content', 'value' => 'content value'],
        ];

        $this->assertEquals($expected, $meta->jsonSerialize());
    }

}
