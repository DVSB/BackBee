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

use org\bovigo\vfs\vfsStream;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use BackBee\DependencyInjection\Container;
use BackBee\DependencyInjection\ContainerBuilder;
use BackBee\DependencyInjection\ContainerProxy;
use BackBee\DependencyInjection\Dumper\PhpArrayDumper;
use BackBee\Tests\Mock\ManualBBApplication;

/**
 * Set of tests for BackBee\DependencyInjection\ContainerBuilder.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\DependencyInjection\ContainerBuilder
 */
class ContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * base application.
     *
     * @var BackBee\ApplicationInterface
     */
    private $application;

    /**
     * this set of tests resources directory (absolute path).
     *
     * @var string
     */
    private $resources_directory;

    /**
     * setup base of test environment.
     */
    public function setUp()
    {
        $this->resources_directory = __DIR__.'/ContainerBuilderTest_Resources';

        // create basic manual application without context and environment
        $this->application = new ManualBBApplication();
        $this->application->setBase_Repository($this->resources_directory.'/repository');
        $this->application->setRepository($this->application->getBaseRepository());
        $this->application->setBB_Dir($this->resources_directory.'/backbee');
        $this->application->setConfig_Dir($this->application->getBaseRepository());
        $this->application->setBase_Dir($this->resources_directory);

        // setup a virtual filesystem to allow dump and restore of container
        vfsStream::setup('container_dump_directory', 0777, array(
            'container' => array(),
        ));
    }

    /**
     * @covers ::getContainer
     */
    public function testGetContainerWithoutContextAndEnvironment()
    {
        $container_builder = new ContainerBuilder($this->application);

        $container = $container_builder->getContainer();
        $this->assertInstanceOf('BackBee\DependencyInjection\Container', $container);

        return $container;
    }

    /**
     * @depends testGetContainerWithoutContextAndEnvironment
     *
     * @covers ::hydrateContainerWithBootstrapParameters
     * @covers ::tryAddParameter
     */
    public function testHydrateContainerWithBootstrapParameters(Container $container)
    {
        $this->assertTrue($container->getParameter('debug', false));
        $this->assertEquals('', $container->getParameter('container.dump_directory', false));
        $this->assertEquals(true, $container->getParameter('container.autogenerate', false));

        return $container;
    }

    /**
     * test that the load of backbee core services and repository services are done is the right order.
     *
     * @depends testHydrateContainerWithBootstrapParameters
     *
     * @covers ::loadApplicationServices
     */
    public function testLoadApplicationServices(Container $container)
    {
        $this->assertEquals('foo', $container->getParameter('foo'));
        $this->assertEquals('BackBee\Logging\Logger', $container->getParameter('bbapp.logger.class'));
        $this->assertEquals(
            'BackBee\Logging\DebugStackLogger',
            $container->getParameter('bbapp.logger_debug.class')
        );

        $this->assertInstanceOf('DateTime', $container->get('core_service'));
        $this->assertInstanceOf('DateTime', $container->get('repository_service'));
        $this->assertNull($container->get('synthetic_service'));
        $this->assertTrue($container->getDefinition('synthetic_service')->isSynthetic());

        return $container;
    }

    /**
     * @depends testLoadApplicationServices
     *
     * @covers ::hydrateContainerWithApplicationParameters
     */
    public function testHydrateContainerWithApplicationParameters(Container $container)
    {
        $this->assertEquals($this->application->getContext(), $container->getParameter('bbapp.context'));
        $this->assertEquals($this->application->getEnvironment(), $container->getParameter('bbapp.environment'));
        $this->assertEquals($this->application->getBBDir(), $container->getParameter('bbapp.base.dir'));
        $this->assertEquals($this->application->getConfigDir(), $container->getParameter('bbapp.config.dir'));
        $this->assertEquals($this->application->getRepository(), $container->getParameter('bbapp.repository.dir'));

        $this->assertEquals(
            implode(DIRECTORY_SEPARATOR, array(
                $this->application->getBaseDir(),
                ContainerBuilder::CACHE_FOLDER_NAME,
            )),
            $container->getParameter('bbapp.cache.dir')
        );

        $this->assertEquals($container->getParameter('bbapp.cache.autogenerate'), '%container.autogenerate%');

        $this->assertEquals(
            $this->application->getRepository().DIRECTORY_SEPARATOR.ContainerBuilder::DATA_FOLDER_NAME,
            $container->getParameter('bbapp.data.dir')
        );

        return $container;
    }

    /**
     * @depends testHydrateContainerWithApplicationParameters
     *
     * @covers ::loadLoggerDefinition
     */
    public function testLoadLoggerDefinition(Container $container)
    {
        $this->assertTrue($container->hasDefinition('logging'));
        $definition = $container->getDefinition('logging');
        $this->assertEquals($container->getParameter('bbapp.logger_debug.class'), $definition->getClass());
        $this->assertNotNull($argument = $definition->getArgument(0));
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $argument);
        $this->assertEquals('bbapp', $argument->__toString());

        return $container;
    }

    /**
     * @depends testLoadLoggerDefinition
     *
     * @covers ::tryParseContainerDump
     * @covers ::getContainerDumpFilename
     */
    public function testTryParseContainerDumpWithDebugTrue(Container $container)
    {
        $this->assertEquals(
            'bb'.md5(
                '__container__'
                .$this->application->getContext()
                .$this->application->getEnvironment()
                .filemtime($container->getParameter('bootstrap_filepath'))
            ),
            $container->getParameter('container.filename')
        );
    }

    /**
     * test if the ContainerAlreadyExistsException is raise when we call a second time the method
     * ContainerBuilder::getContainer().
     *
     * @covers ::getContainer
     */
    public function testRaiseContainerAlreadyExistsException()
    {
        $container_builder = new ContainerBuilder($this->application);

        $container = $container_builder->getContainer();

        try {
            $container_builder->getContainer();
            $this->fail('Raise of ContainerAlreadyExistsException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf('BackBee\DependencyInjection\Exception\ContainerAlreadyExistsException', $e);
            $this->assertEquals($e->getContainer(), $container);
        }
    }

    /**
     * Test to get a container for application which has a context and an environment; debug is setted to false.
     *
     * @covers ::getContainer()
     */
    public function testGetContainerWithContextAndEnvironmentAndDebugFalse()
    {
        $this->application->setContext('test');
        $this->application->setEnvironment('test');
        $this->application->setRepository(
            $this->application->getBaseRepository().DIRECTORY_SEPARATOR.$this->application->getContext()
        );

        $container_builder = new ContainerBuilder($this->application);
        $container = $container_builder->getContainer();

        $this->assertTrue($container->hasDefinition('synthetic_service'));
        $this->assertFalse($container->getDefinition('synthetic_service')->isSynthetic());
        $this->assertInstanceOf('stdClass', $container->get('synthetic_service'));
        $this->assertEquals('world', $container->getParameter('hello'));
        $this->assertEquals('foo', $container->getParameter('foo'));

        return $container;
    }

    /**
     * @depends testGetContainerWithContextAndEnvironmentAndDebugFalse
     *
     * @covers ::tryParseContainerDump
     */
    public function testDumpAndRestoreContainer(Container $container)
    {
        $dump_directory = $container->getParameter('container.dump_directory');
        $dump_filename = $container->getParameter('container.filename');

        $dumper = new PhpArrayDumper($container);
        $dump = $dumper->dump(array('do_compile' => true));

        $container_proxy = new ContainerProxy();
        $dump = unserialize($dump);
        $container_proxy->init($dump);
        $container_proxy->setParameter('services_dump', serialize($dump['services']));
        $container_proxy->setParameter('is_compiled', $dump['is_compiled']);

        file_put_contents(
            $dump_directory.DIRECTORY_SEPARATOR.$dump_filename.'.php',
            (new PhpDumper($container_proxy))->dump(array(
                'class'      => $dump_filename,
                'base_class' => 'BackBee\DependencyInjection\ContainerProxy',
            ))
        );

        $this->assertFileExists($dump_directory.DIRECTORY_SEPARATOR.$dump_filename.'.php');
        $this->assertTrue(is_readable($dump_directory.DIRECTORY_SEPARATOR.$dump_filename.'.php'));

        $this->application->setContext('test');
        $this->application->setEnvironment('test');
        $this->application->setRepository(
            $this->application->getBaseRepository().DIRECTORY_SEPARATOR.$this->application->getContext()
        );

        $container_builder = new ContainerBuilder($this->application);
        $container_proxy = $container_builder->getContainer();

        $this->assertInstanceOf($dump_filename, $container_proxy);
        $this->assertInstanceOf('BackBee\DependencyInjection\ContainerProxy', $container_proxy);
    }

    /**
     * setup a wrong bootstrap.yml to raise MissingBootstrapParametersException.
     *
     * @covers ::hydrateContainerWithBootstrapParameters
     */
    public function testRaiseOfMissingBootstrapParametersException()
    {
        $this->application->setContext('fake_bootstrap');
        $this->application->setRepository(
            $this->application->getBaseRepository().DIRECTORY_SEPARATOR.$this->application->getContext()
        );

        $container_builder = new ContainerBuilder($this->application);

        try {
            $container_proxy = $container_builder->getContainer();
            $this->fail('Raise of MissingBootstrapParametersException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBee\DependencyInjection\Exception\MissingBootstrapParametersException', $e
            );
        }
    }
}
