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

namespace BackBuilder\Rest\Tests\Mapping\Driver;

use BackBuilder\Tests\TestCase;
use BackBuilder\Rest\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;
use BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController;

/**
 * Test for AuthController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBuilder\Rest\Mapping\Driver\AnnotationDriver
 */
class AnnotationDriverTest extends TestCase
{
    protected function setUp()
    {
        // annotations require custom autoloading
        AnnotationRegistry::registerAutoloadNamespaces([
            'Symfony\Component\Validator\Constraint' => $this->getBBApp()->getVendorDir().'/symfony/symfony/src/',
            'JMS\Serializer\Annotation' => $this->getBBApp()->getVendorDir().'/jms/serializer/src/',
            'BackBuilder' => $this->getBBApp()->getBaseDir(),
        ]);
    }

    /**
     * @covers ::__construct
     */
    public function test__construct()
    {
        $driver = new AnnotationDriver(new AnnotationReader());
        $this->assertInstanceOf('BackBuilder\Rest\Mapping\Driver\AnnotationDriver', $driver);
    }

    /**
     * @covers ::loadMetadataForClass
     */
    public function testLoadMetadataForClass()
    {
        $controller = new FixtureAnnotatedController();
        $driver = new AnnotationDriver(new AnnotationReader());
        $reflectionClass = new \ReflectionClass($controller);
        $classMetadata = $driver->loadMetadataForClass($reflectionClass);

        $this->assertArrayHasKey('defaultPaginationAction', $classMetadata->methodMetadata);
        $this->assertArrayHasKey('customPaginationAction', $classMetadata->methodMetadata);
        $this->assertArrayHasKey('requestParamsAction', $classMetadata->methodMetadata);
        $this->assertArrayNotHasKey('justARandomMethod', $classMetadata->methodMetadata);
        $this->assertArrayNotHasKey('privateMethodInvalidAction', $classMetadata->methodMetadata);

        $this->assertEquals(20, $classMetadata->methodMetadata['customPaginationAction']->default_count);
        $this->assertEquals(100, $classMetadata->methodMetadata['customPaginationAction']->max_count);
        $this->assertEquals(10, $classMetadata->methodMetadata['customPaginationAction']->min_count);

        $this->assertEquals(2, count($classMetadata->methodMetadata['requestParamsAction']->requestParams));
        $this->assertEquals(1, count($classMetadata->methodMetadata['requestParamsAction']->queryParams));
    }

    /**
     * @covers ::loadMetadataForClass
     */
    public function testLoadMetadataForClass_tryAbstractController()
    {
        $driver = new AnnotationDriver(new AnnotationReader());
        $reflectionClass = new \ReflectionClass('\BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAbstractController');
        $classMetadata = $driver->loadMetadataForClass($reflectionClass);

        $this->assertArrayHasKey('concreteAction', $classMetadata->methodMetadata);

        // should ignore abstract actions
        $this->assertArrayNotHasKey('abstractAction', $classMetadata->methodMetadata);
    }
}
