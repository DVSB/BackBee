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
use BackBee\MetaData\MetaDataBag;
use BackBee\Tests\BackBeeTestCase;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MetaDataBagTest extends BackBeeTestCase
{

    /**
     * @covers BackBee\MetaData\MetaDataBag::add
     */
    public function testAdd()
    {
        $bag = new MetaDataBag();
        $meta = new MetaData('test');

        $this->assertEquals($bag, $bag->add($meta));
        $this->assertEquals($meta, $bag->get('test'));
    }

    /**
     * @covers BackBee\MetaData\MetaDataBag::has
     */
    public function testHas()
    {
        $bag = new MetaDataBag();
        $bag->add(new MetaData('test'));

        $this->assertTrue($bag->has('test'));
        $this->assertFalse($bag->has('unknown'));
    }

    /**
     * @covers BackBee\MetaData\MetaDataBag::get
     */
    public function testGet()
    {
        $bag = new MetaDataBag();
        $meta = new MetaData('test');
        $bag->add($meta);

        $this->assertEquals($meta, $bag->get('test'));
        $this->assertNull($bag->get('unknown'));
    }

    /**
     * @covers BackBee\MetaData\MetaDataBag::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $meta1 = new MetaData('test1');
        $meta1->setAttribute('name', 'test1');
        $meta1->setAttribute('content', 'content of test1');
        $meta1->setAttribute('lang', 'fr');

        $meta2 = new MetaData('test2');
        $meta2->setAttribute('name', 'test2');
        $meta2->setAttribute('content', 'content of test2');
        $meta2->setAttribute('lang', 'en');

        $bag = new MetaDataBag();
        $bag->add($meta1)->add($meta2);

        $expected = [
            'test1' => [
                'content' => 'content of test1',
                'lang' => 'fr',
            ],
            'test2' => [
                'content' => 'content of test2',
                'lang' => 'en',
            ],
        ];

        $this->assertEquals($expected, $bag->jsonSerialize());
    }

}
