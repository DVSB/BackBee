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
 * Test for BootstrapResolver
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
        $config_test_directory = realpath(__DIR__ . '/PhpArrayDumperTest_Resources');

        if (false === is_dir($config_test_directory)) {
            throw new \Exception(sprintf('Unable to find test Config directory (%s)', $config_test_directory));
        }

        $services_filepath = $config_test_directory . DIRECTORY_SEPARATOR . 'services.yml';
        if (false === is_file($services_filepath) || false === is_readable($services_filepath)) {
            throw new \Exception(sprintf('Unable to find or read services.yml (%s)', $services_filepath));
        }

        $this->raw_services = Yaml::parse($services_filepath);

        ServiceLoader::loadServicesFromYamlFile($this->container, $config_test_directory);

        $this->dump = unserialize($this->dumper->dump());
    }

    /**
     * @covers PhpArrayDumper::dump
     */
    public function testDumpBase()
    {
        $this->assertTrue(is_array($this->dump));

        $this->assertTrue(array_key_exists('parameters', $this->dump));
        $this->assertTrue(array_key_exists('services', $this->dump));
        $this->assertTrue(array_key_exists('aliases', $this->dump));
        $this->assertTrue(array_key_exists('services_dump', $this->dump));
        $this->assertTrue(array_key_exists('is_compiled', $this->dump));
    }

    /**
     * @covers PhpArrayDumper::dump
     * @covers PhpArrayDumper::dumpContainerParameters
     */
    public function testDumpContainerParameters()
    {
        $this->assertEquals($this->raw_services['parameters'], $this->dump['parameters']);
    }

    /**
     * @covers PhpArrayDumper::dump
     * @covers PhpArrayDumper::dumpContainerDefinitions
     * @covers PhpArrayDumper::hydrateDefinitionClass
     * @covers PhpArrayDumper::dumpContainerAliases
     * @covers PhpArrayDumper::hydrateDefinitionArguments
     * @covers PhpArrayDumper::convertDefinitionToPhpArray
     * @covers PhpArrayDumper::convertArgument
     * @covers PhpArrayDumper::hydrateDefinitionTags
     * @covers PhpArrayDumper::hydrateDefinitionMethodCalls
     */
    public function testDumpContainerDefinitionsAndAliases()
    {
        foreach ($this->raw_services['services'] as $id => $definition) {
            if (true === array_key_exists('alias', $definition)) {
                $this->assertTrue(array_key_exists($id, $this->dump['aliases']));
                $this->assertEquals($this->raw_services['services'][$id]['alias'], $this->dump['aliases'][$id]);
            } else {
                $this->assertTrue(array_key_exists($id, $this->dump['services']));
                $this->assertEquals($this->raw_services['services'][$id], $this->dump['services'][$id]);
            }
        }
    }

    /**
     * [testDumpableService description]
     * @return [type] [description]
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

        $this->assertFalse(array_key_exists($config_service_id, $dump['services_dump']));

        $config = $this->container->get($config_service_id);
        $test = array('foo' => 'bar');
        $config->setSection('test', $test);

        $dump = unserialize($this->dumper->dump());

        $this->assertTrue(array_key_exists($config_service_id, $dump['services_dump']));
        $this->assertTrue(array_key_exists('dump', $dump['services_dump'][$config_service_id]));
        $this->assertTrue(array_key_exists('class_proxy', $dump['services_dump'][$config_service_id]));

        $this->assertEquals($config_class_proxy, $dump['services_dump'][$config_service_id]['class_proxy']);

        $config_dump = $dump['services_dump'][$config_service_id]['dump'];

        $this->assertTrue(array_key_exists('basedir', $config_dump));
        $this->assertEquals($config_base_dir, $config_dump['basedir']);

        $this->assertTrue(array_key_exists('raw_parameters', $config_dump));
        $this->assertEquals(array('test' => $test), $config_dump['raw_parameters']);

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
     * [testNotDumpableService description]
     * @return [type] [description]
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
     * @covers PhpArrayDumper::dump
     */
    public function testDumpContainerCompile()
    {
        $this->assertFalse($this->dump['is_compiled']);
        $this->dump = unserialize($this->dumper->dump(array('do_compile' => true)));
        $this->assertTrue($this->dump['is_compiled']);
        $this->assertTrue($this->container->isFrozen());
    }
}
