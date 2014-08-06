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

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\Serializer\Encoder\JsonEncoder;

use Doctrine\Common\Annotations\AnnotationRegistry,
    Doctrine\Common\Annotations\AnnotationReader;

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
        $actionMetadata->paginationStartName = 'start';
        $actionMetadata->paginationLimitName = 'limit';
        $actionMetadata->paginationLimitDefault = 50;
        $actionMetadata->paginationLimitMax = 500;
        $actionMetadata->paginationLimitMin = 50;

    
        $this->assertEquals(serialize([
            'BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController', 
            'customPaginationAction', 
            [], 
            [],
            $actionMetadata->paginationStartName,
            $actionMetadata->paginationLimitName,
            $actionMetadata->paginationLimitDefault,
            $actionMetadata->paginationLimitMax,
            $actionMetadata->paginationLimitMin
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
            'start',
            'limit',
            50,
            500,
            50
        ]);
        
        $ref = new \ReflectionClass('\BackBuilder\Rest\Mapping\ActionMetadata');
        $actionMetadata = $ref->newInstanceWithoutConstructor();
        
        $actionMetadata->unserialize($serialized);
        
        $this->assertEquals('BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController', $actionMetadata->class);
        $this->assertEquals('customPaginationAction', $actionMetadata->name);
        $this->assertEquals([], $actionMetadata->queryParams);
        $this->assertEquals([], $actionMetadata->requestParams);
        $this->assertEquals('start', $actionMetadata->paginationStartName);
        $this->assertEquals('limit', $actionMetadata->paginationLimitName);
        $this->assertEquals(50, $actionMetadata->paginationLimitDefault);
        $this->assertEquals(500, $actionMetadata->paginationLimitMax);
        $this->assertEquals(50, $actionMetadata->paginationLimitMin);
      
    }
    
    
    
}