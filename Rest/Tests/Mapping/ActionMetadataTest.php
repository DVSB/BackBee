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

namespace BackBuilder\Rest\Tests\Mapping;

use BackBuilder\Tests\TestCase;
use BackBuilder\Rest\Mapping\ActionMetadata;

/**
 * Test for AuthController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBuilder\Rest\Mapping\ActionMetadata
 */
class ActionMetadataTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function test__construct()
    {
        $actionMetadata = new ActionMetadata('BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController', 'customPaginationAction');

        $this->assertEquals('BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController', $actionMetadata->class);
        $this->assertEquals('customPaginationAction', $actionMetadata->name);
        $this->assertInstanceOf('\ReflectionMethod', $actionMetadata->reflection);
    }

    /**
     * @covers ::serialize
     */
    public function testSerialize()
    {
        $actionMetadata = new ActionMetadata('BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController', 'customPaginationAction');
        $actionMetadata->default_start = 1;
        $actionMetadata->default_count = 20;
        $actionMetadata->max_count = 500;
        $actionMetadata->min_count = 2;

        $this->assertEquals([], $actionMetadata->queryParams);
        $this->assertEquals([], $actionMetadata->requestParams);
        $this->assertEquals(1, $actionMetadata->default_start);
        $this->assertEquals(20, $actionMetadata->default_count);
        $this->assertEquals(500, $actionMetadata->max_count);
        $this->assertEquals(2, $actionMetadata->min_count);
        $this->assertEquals([], $actionMetadata->param_converter_bag);
        $this->assertEquals([], $actionMetadata->security);

        $this->assertEquals(serialize([
            'BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController',
            'customPaginationAction',
            [],
            [],
            $actionMetadata->default_start,
            $actionMetadata->default_count,
            $actionMetadata->max_count,
            $actionMetadata->min_count,
            [],
            [],
        ]), $actionMetadata->serialize());
    }

    /**
     * @covers ::unserialize
     */
    public function testUnserialize()
    {
        $serialized = serialize([
            'BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController',
            'customPaginationAction',
            [],
            [],
            1,
            20,
            500,
            2,
            [],
            [],
        ]);

        $ref = new \ReflectionClass('\BackBuilder\Rest\Mapping\ActionMetadata');
        $actionMetadata = $ref->newInstanceWithoutConstructor();

        $actionMetadata->unserialize($serialized);

        $this->assertEquals('BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController', $actionMetadata->class);
        $this->assertEquals('customPaginationAction', $actionMetadata->name);
        $this->assertEquals([], $actionMetadata->queryParams);
        $this->assertEquals([], $actionMetadata->requestParams);
        $this->assertEquals(1, $actionMetadata->default_start);
        $this->assertEquals(20, $actionMetadata->default_count);
        $this->assertEquals(500, $actionMetadata->max_count);
        $this->assertEquals(2, $actionMetadata->min_count);
        $this->assertEquals([], $actionMetadata->param_converter_bag);
        $this->assertEquals([], $actionMetadata->security);
    }
}
