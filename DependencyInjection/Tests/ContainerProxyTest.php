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

namespace BackBee\DependencyInjection\Tests;

use BackBee\DependencyInjection\Container;
use BackBee\DependencyInjection\ContainerInterface;
use BackBee\DependencyInjection\ContainerProxy;
use BackBee\DependencyInjection\Dumper\PhpArrayDumper;
use BackBee\DependencyInjection\Util\ServiceLoader;
use BackBee\Tests\TestKernel;

use org\bovigo\vfs\vfsStream;

use Symfony\Component\Yaml\Yaml;

/**
 * Test for ContainerProxy.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\DependencyInjection\ContainerProxy
 */
class ContainerProxyTest extends \PHPUnit_Framework_TestCase
{
    const RANDOM_SERVICE_NEW_SIZE_VALUE = 42;

    private $container;

    /**
     * setup the environment for test.
     */
    public function setUp()
    {
        $servicesYmlArray = [
            'parameters' => [
                'service.class' => 'BackBee\DependencyInjection\Tests\RandomService',
                'size_one'      => 300,
                'size_two'      => 8000,
                'size_three'    => 44719,
            ],
            'services'   => [
                'service_one' => [
                    'class'     => '%service.class%',
                    'arguments' => ['%size_two%'],
                ],
                'service_two' => [
                    'class'     => '%service.class%',
                    'calls'     => [
                        ['setSize', ['%size_three%']],
                    ],
                    'tags'  => [
                        ['name' => 'dumpable'],
                    ],
                ],
                'service_three' => [
                    'class'     => '%service.class%',
                    'calls'     => [
                        ['setClassProxy', ['']],
                    ],
                    'tags'  => [
                        ['name' => 'dumpable'],
                    ],
                ],
                'service_four' => [
                    'class' => '%service.class%',
                    'tags'  => [
                        ['name' => 'foo'],
                        ['name' => 'bar'],
                    ],
                ],
                'service_five' => [
                    'synthetic' => true,
                ],
                'service_six' => [
                    'class'  => '%service.class%',
                    'public' => false,
                ],
                'service_seven' => [
                    'class'        => '%service.class%',
                    'scope'        => 'prototype',
                    'file'         => '/foo/bar',
                    'configurator' => ['@service_six', 'getSize'],
                ],
                'service_eight' => [
                    'class'   => '%service.class%',
                    'factory' => ['\DateTime', 'getLastErrors'],
                ],
                'service_nine' => [
                    'class'   => '%service.class%',
                    'factory' => ['@service_zero', 'get'],
                ],
                'service_ten' => [
                    'abstract' => true,
                    'calls'    => [
                        ['setSize', [8]],
                    ],
                ],
                'service_eleven' => [
                    'class'  => '%service.class%',
                    'parent' => 'service_ten',
                ],
            ],
        ];

        $this->container = new Container();

        vfsStream::setup('directory', 0777, [
            'services.yml' => Yaml::dump($servicesYmlArray),
        ]);

        ServiceLoader::loadServicesFromYamlFile($this->container, vfsStream::url('directory'));
        $this->container->get('service_two')->setSize(self::RANDOM_SERVICE_NEW_SIZE_VALUE);
    }

    public function testGetParameter()
    {
        $dumper = new PhpArrayDumper($this->container);

        $container = new ContainerProxy();
        $container->init(unserialize($dumper->dump(['do_compile' => false])));

        $this->assertEquals($this->container->getParameterBag()->all(), $container->getParameterBag()->all());

        return $container;
    }

    /**
     * @depends testGetParameter
     */
    public function testGet(ContainerInterface $container)
    {
        $original_service = $this->container->get('service_one');
        $service = $container->get('service_one');

        $this->assertEquals(get_class($original_service), get_class($service));
        $this->assertNotEquals(spl_object_hash($original_service), spl_object_hash($service));
        $this->assertEquals($original_service->getSize(), $service->getSize());

        return $container;
    }

    /**
     * @depends testGet
     */
    public function testTryRestoreDumpableService(ContainerInterface $container)
    {
        $original_service = $this->container->get('service_two');
        $service = $container->get('service_two');

        $this->assertNotEquals(get_class($original_service), get_class($service));
        $this->assertEquals(RandomService::RANDOM_SERVICE_PROXY_CLASSNAME, get_class($service));
        $this->assertEquals($original_service->getSize(), $service->getSize());
        $this->assertEquals(self::RANDOM_SERVICE_NEW_SIZE_VALUE, $service->getSize());

        try {
            $this->container->get('service_three');
            $dumper = new PhpArrayDumper($this->container);
            $dumper->dump(array('do_compile' => false));
            $this->fail('Raise of InvalidServiceProxyException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBee\DependencyInjection\Exception\InvalidServiceProxyException',
                $e
            );
        }

        return $container;
    }

    /**
     * @depends testTryRestoreDumpableService
     */
    public function testHas(ContainerInterface $container)
    {
        $this->assertFalse($container->has('service_zero'));
        $this->assertTrue(
            $container->has('service_one')
            && $container->has('service_two')
            && $container->has('service_three')
            && $container->has('service_four')
            && $container->has('service_five')
            && $container->has('service_six')
            && $container->has('service_seven')
            && $container->has('service_eight')
            && $container->has('service_nine')
            && $container->has('service_ten')
            && $container->has('service_eleven')
        );

        return $container;
    }

    /**
     * @depends testHas
     */
    public function testHasDefinition(ContainerInterface $container)
    {
        $this->assertFalse($container->hasDefinition('service_zero'));
        $this->assertTrue(
            $container->hasDefinition('service_one')
            && $container->hasDefinition('service_two')
            && $container->hasDefinition('service_three')
            && $container->hasDefinition('service_four')
            && $container->hasDefinition('service_five')
            && $container->hasDefinition('service_six')
            && $container->hasDefinition('service_seven')
            && $container->hasDefinition('service_eight')
            && $container->hasDefinition('service_nine')
            && $container->hasDefinition('service_ten')
            && $container->hasDefinition('service_eleven')
        );

        return $container;
    }

    /**
     * @depends testHasDefinition
     */
    public function testGetDefinition(ContainerInterface $container)
    {
        try {
            $container->getDefinition('service_zero');
            $this->fail('Raise of InvalidArgumentException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Symfony\Component\DependencyInjection\Exception\InvalidArgumentException', $e);
        }

        // Test restoration of synthetic service definition
        $this->assertFalse($container->getDefinition('service_four')->isSynthetic());
        $this->assertTrue($container->getDefinition('service_five')->isSynthetic());

        // Test restoration of service definition tag
        $this->assertTrue($container->getDefinition('service_three')->hasTag('dumpable'));
        $this->assertTrue($container->getDefinition('service_four')->hasTag('foo'));
        $this->assertTrue($container->getDefinition('service_four')->hasTag('bar'));

        // Test restoration of service public, scope, abstract and file values
        $this->assertFalse($container->getDefinition('service_six')->isPublic());
        $this->assertEquals($container->getDefinition('service_seven')->getScope(), 'prototype');
        $this->assertEquals($container->getDefinition('service_seven')->getFile(), '/foo/bar');
        $this->assertTrue($container->getDefinition('service_ten')->isAbstract());

        // Test restoration of service configurator
        $configurator = $container->getDefinition('service_seven')->getConfigurator();
        $this->assertNotNull($configurator);
        $this->assertEquals(2, count($configurator));
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $configurator[0]);
        $this->assertEquals('service_six', $configurator[0]->__toString());
        $this->assertEquals('getSize', $configurator[1]);

        // Test restoration of service factory class, factory service and factory method
        $factory = $container->getDefinition('service_eight')->getFactory();
        $this->assertEquals('\DateTime', $factory[0]);
        $this->assertEquals('getLastErrors', $factory[1]);

        $factory = $container->getDefinition('service_nine')->getFactory();
        $this->assertEquals('service_zero', $factory[0]->__toString());
        $this->assertEquals('get', $factory[1]);

        // Test restoration of service with parent (without compilation)
        $this->assertTrue($container->hasDefinition('service_ten'));
        $this->assertInstanceOf(
            'Symfony\Component\DependencyInjection\DefinitionDecorator',
            $container->getDefinition('service_eleven')
        );

        $this->assertEmpty($container->getDefinition('service_eleven')->getMethodCalls());
        $parent_method_calls = $container->getDefinition('service_ten')->getMethodCalls();
        $this->assertNotEmpty($parent_method_calls);

        // Test restoration of service with parent (with compilation)
        $dumper = new PhpArrayDumper($this->container);

        $container = new ContainerProxy();
        $container->init(unserialize($dumper->dump(array('do_compile' => true))));

        $this->assertFalse($container->hasDefinition('service_ten'));
        $this->assertInstanceOf(
            'Symfony\Component\DependencyInjection\Definition',
            $container->getDefinition('service_eleven')
        );
        $this->assertEquals($parent_method_calls, $container->getDefinition('service_eleven')->getMethodCalls());
    }

    public function testIsCompiled()
    {
        // test that ContainerProxy::isCompiled return false value
        $dumper = new PhpArrayDumper($this->container);

        $container = new ContainerProxy();
        $container->init(unserialize($dumper->dump(array('do_compile' => false))));

        $this->assertInstanceOf('BackBee\DependencyInjection\ContainerProxy', $container);
        $this->assertFalse($container->isCompiled());

        // test that ContainerProxy::isCompiled return false value
        $dumper = new PhpArrayDumper($this->container);

        $container = new ContainerProxy();
        $container->init(unserialize($dumper->dump(array('do_compile' => true))));

        $this->assertTrue($container->isCompiled());
    }

    public static function tearDownAfterClass()
    {
        TestKernel::getInstance()->getApplication()->resetStructure();
    }
}
