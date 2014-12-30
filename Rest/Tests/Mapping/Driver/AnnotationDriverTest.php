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

namespace BackBee\Rest\Tests\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

use BackBee\Rest\Mapping\Driver\AnnotationDriver;
use BackBee\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController;
use BackBee\Tests\TestCase;

/**
 * Test for AuthController class
 *
 * @category    BackBee
 * @package     BackBee\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\Mapping\Driver\AnnotationDriver
 */
class AnnotationDriverTest extends TestCase
{
    protected function setUp()
    {
        // annotations require custom autoloading
        AnnotationRegistry::registerAutoloadNamespaces([
            'Symfony\Component\Validator\Constraint' => $this->getBBApp()->getVendorDir().'/symfony/symfony/src/',
            'JMS\Serializer\Annotation' => $this->getBBApp()->getVendorDir().'/jms/serializer/src/',
            'BackBee' => $this->getBBApp()->getBaseDir(),
        ]);
    }

    /**
     * @covers ::__construct
     */
    public function test__construct()
    {
        $driver = new AnnotationDriver(new AnnotationReader());
        $this->assertInstanceOf('BackBee\Rest\Mapping\Driver\AnnotationDriver', $driver);
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
        $reflectionClass = new \ReflectionClass('\BackBee\Rest\Tests\Fixtures\Controller\FixtureAbstractController');
        $classMetadata = $driver->loadMetadataForClass($reflectionClass);

        $this->assertArrayHasKey('concreteAction', $classMetadata->methodMetadata);

        // should ignore abstract actions
        $this->assertArrayNotHasKey('abstractAction', $classMetadata->methodMetadata);
    }
}
