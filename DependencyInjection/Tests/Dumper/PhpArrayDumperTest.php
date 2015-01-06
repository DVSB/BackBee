<?php
namespace BackBuilder\DependencyInjection\Tests\Dumper;

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

use BackBuilder\DependencyInjection\Container;
use BackBuilder\DependencyInjection\Dumper\PhpArrayDumper;
use BackBuilder\DependencyInjection\Util\ServiceLoader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Yaml;

/**
 * Test for PhpArrayDumper
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBuilder\DependencyInjection\Dumper\PhpArrayDumper
 */
class PhpArrayDumperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The dumper we want to test here
     *
     * @var BackBuilder\DependencyInjection\Dump\PhpArrayDumper
     */
    private $dumper;

    /**
     * container used for test
     *
     * @var \BackBuilder\DependencyInjection\Container
     */
    private $container;

    /**
     * container dump generate by PhpArrayDumper::dump() (the result is unserialized)
     *
     * @var array
     */
    private $dump;

    /**
     * array that contains the parse of test context services.yml
     *
     * @var array
     */
    private $raw_services;

    /**
     * setup the environment for test
     */
    public function setUp()
    {
        $this->container = new Container();
        $this->dumper = new PhpArrayDumper($this->container);
        $config_test_directory = realpath(__DIR__.'/PhpArrayDumperTest_Resources');

        if (false === is_dir($config_test_directory)) {
            throw new \Exception(sprintf('Unable to find test Config directory (%s)', $config_test_directory));
        }

        $services_filepath = $config_test_directory.DIRECTORY_SEPARATOR.'services.yml';
        if (false === is_file($services_filepath) || false === is_readable($services_filepath)) {
            throw new \Exception(sprintf('Unable to find or read services.yml (%s)', $services_filepath));
        }

        $this->raw_services = Yaml::parse($services_filepath);

        ServiceLoader::loadServicesFromYamlFile($this->container, $config_test_directory);

        $this->dump = unserialize($this->dumper->dump());
    }

    /**
     * @covers ::dump
     */
    public function testDumpBase()
    {
        $this->assertTrue(is_array($this->dump));

        $this->assertTrue(array_key_exists('parameters', $this->dump));
        $this->assertTrue(array_key_exists('services', $this->dump));
        $this->assertTrue(array_key_exists('aliases', $this->dump));
        $this->assertTrue(array_key_exists('is_compiled', $this->dump));
    }

    /**
     * @covers ::dump
     * @covers ::dumpContainerParameters
     */
    public function testDumpContainerParameters()
    {
        $this->assertEquals($this->raw_services['parameters'], $this->dump['parameters']);
    }

    /**
     * @covers ::dump
     * @covers ::dumpContainerDefinitions
     * @covers ::convertDefinitionToPhpArray
     * @covers ::hydrateDefinitionClass
     * @covers ::hydrateDefinitionArguments
     * @covers ::hydrateDefinitionTags
     * @covers ::hydrateDefinitionMethodCalls
     * @covers ::convertArgument
     */
    public function testDumpContainerBasicsDefinitions()
    {
        $this->assertTrue(array_key_exists('france.brazil.date', $this->dump['services']));
        $this->assertTrue(array_key_exists('france.brazil.date', $this->raw_services['services']));

        // Test definition's class
        $this->assertTrue(array_key_exists('class', $this->dump['services']['france.brazil.date']));
        $this->assertTrue(array_key_exists('class', $this->raw_services['services']['france.brazil.date']));
        $this->assertEquals(
            $this->dump['services']['france.brazil.date']['class'],
            $this->raw_services['services']['france.brazil.date']['class']
        );

        // Test definition's arguments
        $this->assertTrue(array_key_exists('arguments', $this->dump['services']['france.brazil.date']));
        $this->assertTrue(array_key_exists('arguments', $this->raw_services['services']['france.brazil.date']));
        $this->assertEquals(
            $this->dump['services']['france.brazil.date']['arguments'],
            $this->raw_services['services']['france.brazil.date']['arguments']
        );

        // Test definition's tags
        $this->assertTrue(array_key_exists('tags', $this->dump['services']['france.brazil.date']));
        $this->assertTrue(array_key_exists('tags', $this->raw_services['services']['france.brazil.date']));
        $this->assertEquals(
            $this->dump['services']['france.brazil.date']['tags'],
            $this->raw_services['services']['france.brazil.date']['tags']
        );

        // Test definition's calls
        $this->assertTrue(array_key_exists('calls', $this->dump['services']['france.brazil.date']));
        $this->assertTrue(array_key_exists('calls', $this->raw_services['services']['france.brazil.date']));
        $this->assertEquals(
            $this->dump['services']['france.brazil.date']['calls'],
            $this->raw_services['services']['france.brazil.date']['calls']
        );
    }

    /**
     * @covers ::dumpContainerAliases
     * @covers ::convertDefinitionToPhpArray
     */
    public function testDumpContainerAliases()
    {
        $this->assertTrue(array_key_exists('alias.test', $this->dump['aliases']));
        $this->assertTrue(array_key_exists('alias.test', $this->raw_services['services']));
        $this->assertTrue(array_key_exists('alias', $this->raw_services['services']['alias.test']));

        $this->assertEquals(
            $this->raw_services['services']['alias.test']['alias'],
            $this->dump['aliases']['alias.test']
        );
    }

    /**
     * @covers ::hydrateDefinitionScopeProperty
     * @covers ::convertDefinitionToPhpArray
     */
    public function testHydrateDefinitionScopeProperty()
    {
        $this->assertEquals(
            $this->raw_services['services']['service_scope_prototype']['scope'],
            $this->dump['services']['service_scope_prototype']['scope']
        );

        // Test that if scope is equals to `container` (the default value), PhpArrayDumper won't dump it
        $this->assertFalse(array_key_exists('scope', $this->dump['services']['service_scope_container']));
    }

    /**
     * @covers ::hydrateDefinitionPublicProperty
     * @covers ::convertDefinitionToPhpArray
     */
    public function testHydrateDefinitionPublicProperty()
    {
        $this->assertEquals(
            $this->raw_services['services']['service_public_false']['public'],
            $this->dump['services']['service_public_false']['public']
        );

        // Test that if public is equals to `true` (the default value), PhpArrayDumper won't dump it
        $this->assertFalse(array_key_exists('public', $this->dump['services']['service_public_true']));
    }

    /**
     * @covers ::hydrateDefinitionFileProperty
     * @covers ::convertDefinitionToPhpArray
     */
    public function testHydrateDefinitionFileProperty()
    {
        $this->assertEquals(
            $this->raw_services['services']['service_with_file']['file'],
            $this->dump['services']['service_with_file']['file']
        );
    }

    /**
     * @covers ::hydrateDefinitionFactoryClass
     * @covers ::hydrateDefinitionFactoryService
     * @covers ::hydrateDefinitionFactoryMethod
     * @covers ::convertDefinitionToPhpArray
     */
    public function testContainerDumpFactoryServiceAndClassAndMethod()
    {
        // Test factory class
        $this->assertEquals(
            $this->raw_services['services']['service_factory_class']['factory_class'],
            $this->dump['services']['service_factory_class']['factory_class']
        );

        $this->assertEquals(
            $this->raw_services['services']['service_factory_class']['factory_method'],
            $this->dump['services']['service_factory_class']['factory_method']
        );

        // Test factory service
        $this->assertEquals(
            $this->raw_services['services']['service_factory_service']['factory_service'],
            $this->dump['services']['service_factory_service']['factory_service']
        );

        $this->assertEquals(
            $this->raw_services['services']['service_factory_service']['factory_method'],
            $this->dump['services']['service_factory_service']['factory_method']
        );
    }

    /**
     * @covers ::hydrateDefinitionAbstractProperty
     * @covers ::hydrateDefinitionParent
     * @covers ::convertDefinitionToPhpArray
     */
    public function testContainerDumpParentAndChildService()
    {
        // Test parent service
        $this->assertTrue($this->dump['services']['datetime_manager']['abstract']);
        $this->assertEquals(
            $this->raw_services['services']['datetime_manager']['abstract'],
            $this->dump['services']['datetime_manager']['abstract']
        );

        // Test child service
        $this->assertTrue(array_key_exists('parent', $this->dump['services']['datetime_with_parent']));
        // Test that if abstract is equals to `false` (the default value), PhpArrayDumper won't dump it
        $this->assertFalse(array_key_exists('abstract', $this->dump['services']['datetime_with_parent']));

        $this->assertEquals(
            $this->raw_services['services']['datetime_with_parent']['parent'],
            $this->dump['services']['datetime_with_parent']['parent']
        );
    }

    /**
     * @covers ::hydrateDefinitionConfigurator
     * @covers ::convertDefinitionToPhpArray
     */
    public function testhydrateDefinitionConfigurator()
    {
        $this->assertTrue(array_key_exists('service.use.configurator', $this->dump['services']));
        $this->assertTrue(array_key_exists('service.use.configurator', $this->raw_services['services']));

        $this->assertEquals(
            $this->raw_services['services']['service.use.configurator']['configurator'],
            $this->dump['services']['service.use.configurator']['configurator']
        );
    }

    /**
     * @covers ::dumpDumpableServices
     */
    public function testDumpableService()
    {
        $config_service_id = 'config';
        $config_base_dir = '';
        $config_cache = null;
        $config_container = $this->container;
        $config_debug = false;
        $config_yml_to_ignore = array('services');
        $config_class_proxy = '\BackBuilder\Config\ConfigProxy';

        $config_definition = new Definition();
        $config_definition->setClass('BackBuilder\Config\Config');
        $config_definition->addTag('dumpable');
        $config_definition->addArgument($config_base_dir);
        $config_definition->addArgument($config_cache);
        $config_definition->addArgument($config_container);
        $config_definition->addArgument($config_debug);
        $config_definition->addArgument($config_yml_to_ignore);

        $this->container->setDefinition($config_service_id, $config_definition);

        $dump = unserialize($this->dumper->dump());

        $this->assertNotEquals('BackBuilder\Config\ConfigProxy', $dump['services'][$config_service_id]['class']);

        $config = $this->container->get($config_service_id);
        $test = array('foo' => 'bar');
        $config->setSection('test', $test);

        $dump = unserialize($this->dumper->dump());

        $this->assertEquals('BackBuilder\Config\ConfigProxy', $dump['services'][$config_service_id]['class']);
        $this->assertFalse(array_key_exists('arguments', $dump['services'][$config_service_id]));
        $this->assertTrue(array_key_exists('calls', $dump['services'][$config_service_id]));

        $restore_call_args = null;
        foreach ($dump['services'][$config_service_id]['calls'] as $call) {
            if (true === isset($call[0]) && 'restore' === $call[0]) {
                $restore_call_args = $call[1];
            }
        }

        $this->assertNotNull($restore_call_args);
        $this->assertTrue(
            2 === count($restore_call_args)
            && true === isset($restore_call_args[0])
            && isset($restore_call_args[1])
        );

        $config_dump = $restore_call_args[1];
        $this->assertTrue(is_array($config_dump));

        $this->assertTrue(array_key_exists('basedir', $config_dump));
        $this->assertEquals($config_base_dir, $config_dump['basedir']);

        $this->assertTrue(array_key_exists('raw_parameters', $config_dump));
        $this->assertEquals($test, $config_dump['raw_parameters']['test']);

        $this->assertTrue(array_key_exists('debug', $config_dump));
        $this->assertEquals($config_debug, $config_dump['debug']);

        $this->assertTrue(array_key_exists('yml_names_to_ignore', $config_dump));
        $this->assertEquals($config_yml_to_ignore, $config_dump['yml_names_to_ignore']);

        $this->assertTrue(array_key_exists('has_cache', $config_dump));
        $this->assertEquals(null !== $config_cache, $config_dump['has_cache']);

        $this->assertTrue(array_key_exists('has_container', $config_dump));
        $this->assertEquals(null !== $config_container, $config_dump['has_container']);
    }

    /**
     * @covers ::dumpDumpableServices
     */
    public function testNotDumpableService()
    {
        $id = 'not.dumpable_service';
        $definition = new Definition();
        $definition->setClass('DateTime');
        $definition->addTag('dumpable');
        $definition->addArgument('now');
        $definition->addArgument(new Reference('timezone'));

        $this->container->setDefinition($id, $definition);
        $not_dumpable_service = $this->container->get($id);

        try {
            $this->dumper->dump();
            $this->fail('Raise of ServiceNotDumpableException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf('BackBuilder\DependencyInjection\Exception\ServiceNotDumpableException', $e);
        }
    }

    /**
     * @covers ::dump
     */
    public function testDumpContainerCompile()
    {
        $this->assertFalse($this->dump['is_compiled']);
        $this->dump = unserialize($this->dumper->dump(array('do_compile' => true)));
        $this->assertTrue($this->dump['is_compiled']);
        $this->assertTrue($this->container->isFrozen());
    }
}
