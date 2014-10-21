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

namespace BackBuilder\DependencyInjection\Tests;

use BackBuilder\DependencyInjection\Container;
use BackBuilder\DependencyInjection\ContainerInterface;
use BackBuilder\DependencyInjection\Dumper\PhpArrayDumper;
use BackBuilder\DependencyInjection\ContainerProxy;
use BackBuilder\DependencyInjection\Util\ServiceLoader;

use Symfony\Component\Yaml\Yaml;

use org\bovigo\vfs\vfsStream;

/**
 * Test for ContainerProxy
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBuilder\DependencyInjection\ContainerProxy
 */
class ContainerProxyTest extends \PHPUnit_Framework_TestCase
{
    const RANDOM_SERVICE_NEW_SIZE_VALUE = 42;

    /**
     * [$container description]
     *
     * @var [type]
     */
    private $container;

    /**
     * [$services_yml_array description]
     *
     * @var array
     */
    private $services_array;

    /**
     * setup the environment for test
     */
    public function setUp()
    {
        $this->services_yml_array = array(
            'parameters' => array(
                'service.class' => 'BackBuilder\DependencyInjection\Tests\RandomService',
                'size_one'      => 300,
                'size_two'      => 8000,
                'size_three'    => 44719
            ),
            'services'   => array(
                'service_one' => array(
                    'class'     => '%service.class%',
                    'arguments' => array('%size_two%')
                ),
                'service_two' => array(
                    'class'     => '%service.class%',
                    'calls'     => array(
                        array('setSize', array('%size_three%'))
                    ),
                    'tags'  => array(
                        array('name' => 'dumpable')
                    )
                ),
                'service_three' => array(
                    'class'     => '%service.class%',
                    'calls'     => array(
                        array('setClassProxy', array(''))
                    ),
                    'tags'  => array(
                        array('name' => 'dumpable')
                    )
                ),
                'service_four' => array(
                    'class' => '%service.class%',
                    'tags'  => array(
                        array('name' => 'foo'),
                        array('name' => 'bar')
                    )
                ),
                'service_five' => array(
                    'synthetic' => true
                ),
                'service_six' => array(
                    'class'  => '%service.class%',
                    'public' => false
                ),
                'service_seven' => array(
                    'class'        => '%service.class%',
                    'scope'        => 'prototype',
                    'file'         => '/foo/bar',
                    'configurator' => array('@service_six', 'getSize')
                ),
                'service_eight' => array(
                    'class'          => '%service.class%',
                    'factory_class'  => '/foo/bar/ServiceFactory',
                    'factory_method' => 'get'
                ),
                'service_nine' => array(
                    'class'           => '%service.class%',
                    'factory_service' => 'service_zero',
                    'factory_method'  => 'get'
                ),
                'service_ten' => array(
                    'abstract' => true,
                    'calls'    => array(
                        array('setSize', array(8))
                    )
                ),
                'service_eleven' => array(
                    'class'  => '%service.class%',
                    'parent' => 'service_ten'
                )
            )
        );

        $this->container = new Container();

        vfsStream::setup('directory', 0777, array(
            'services.yml' => Yaml::dump($this->services_yml_array)
        ));

        ServiceLoader::loadServicesFromYamlFile($this->container, vfsStream::url('directory'));

        $this->container->get('service_two')->setSize(self::RANDOM_SERVICE_NEW_SIZE_VALUE);
        // $this->container->get('service_three');
    }

    /**
     * @covers ::__construct
     */
    public function testGetParameter()
    {
        $dumper = new PhpArrayDumper($this->container);

        // vfsStream::setup('directory', 0777, array(
        //     'dump' => $dumper->dump(array('do_compile' => false))
        // ));

        $container = new ContainerProxy();
        $container->init(unserialize($dumper->dump(array('do_compile' => false))));

        $this->assertEquals($this->container->getParameterBag()->all(), $container->getParameterBag()->all());

        return $container;
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::tryLoadDefinitionFromRaw
     * @covers ::tryRestoreDumpableService
     * @covers ::buildDefinition
     * @covers ::setDefinitionClass
     * @covers ::setDefinitionArguments
     * @covers ::convertArgument
     *
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
     * @covers ::__construct
     * @covers ::get
     * @covers ::tryLoadDefinitionFromRaw
     * @covers ::tryRestoreDumpableService
     * @covers ::setDefinitionTags
     * @covers ::setDefinitionMethodCalls
     * @covers ::convertArgument
     *
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
                'BackBuilder\DependencyInjection\Exception\InvalidServiceProxyException',
                $e
            );
        }

        return $container;
    }

    /**
     * @covers ::__construct
     * @covers ::has
     * @covers ::tryLoadDefinitionFromRaw
     *
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
     * @covers ::__construct
     * @covers ::hasDefinition
     * @covers ::tryLoadDefinitionFromRaw
     *
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
     * @covers ::__construct
     * @covers ::getDefinition
     * @covers ::tryLoadDefinitionFromRaw
     * @covers ::buildDefinition
     * @covers ::setDefinitionTags
     * @covers ::setDefinitionProperties
     * @covers ::setDefinitionFactoryClass
     * @covers ::setDefinitionFactoryService
     * @covers ::setDefinitionFactoryMethod
     * @covers ::setDefinitionConfigurator
     * @covers ::convertArgument
     *
     * @covers ::loadRawDefinitions
     *
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
        $this->assertEquals($container->getDefinition('service_eight')->getFactoryClass(), '/foo/bar/ServiceFactory');
        $this->assertEquals($container->getDefinition('service_eight')->getFactoryMethod(), 'get');
        $this->assertEquals($container->getDefinition('service_nine')->getFactoryService(), 'service_zero');
        $this->assertEquals($container->getDefinition('service_nine')->getFactoryMethod(), 'get');

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

    /**
     * @covers ::isCompiled
     */
    public function testIsCompiled()
    {
        // test that ContainerProxy::isCompiled return false value
        $dumper = new PhpArrayDumper($this->container);

        $container = new ContainerProxy();
        $container->init(unserialize($dumper->dump(array('do_compile' => false))));

        $this->assertInstanceOf('BackBuilder\DependencyInjection\ContainerProxy', $container);
        $this->assertFalse($container->isCompiled());

        // test that ContainerProxy::isCompiled return false value
        $dumper = new PhpArrayDumper($this->container);

        $container = new ContainerProxy();
        $container->init(unserialize($dumper->dump(array('do_compile' => true))));

        $this->assertTrue($container->isCompiled());
    }
}
