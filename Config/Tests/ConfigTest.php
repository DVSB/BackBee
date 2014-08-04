<?php
namespace BackBuilder\Config\Tests;

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

use BackBuilder\Config\Config;
use BackBuilder\DependencyInjection\Container;

/**
 * Set of tests for BackBuilder\Config\Config
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass BackBuilder\Config\Config
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * the directory which contains every resources required for running this test class
     *
     * @var string
     */
    private $test_base_dir;

    /**
     * initialize the main directory to looking for ConfigTest resources
     */
    public function setUp()
    {
        $this->test_base_dir = __DIR__ . '/ConfigTest_Resources';
    }

    /**
     * test the Config constructor
     *
     * @covers ::extend
     * @covers ::_loadFromBaseDir
     * @covers ::_getYmlFiles
     * @covers ::_loadFromFile
     * @covers ::sectionHasKey
     * @covers ::getSection
     * @covers ::getBaseDir
     */
    public function testConstruct()
    {
        $config = new Config($this->test_base_dir);

        $this->assertEquals($this->test_base_dir, $config->getBaseDir());
        $this->assertTrue($config->sectionHasKey('say', 'hello'));
        $this->assertFalse($config->sectionHasKey('say', 'hi'));
        $this->assertEquals($config->getSection('say'), array(
            'hello'   => 'world',
            'bonjour' => 'monde',
            'hola'    => 'mundo'
        ));
    }

    /**
     * test extend() with environment or not and override or not
     *
     * @covers ::extend
     * @covers ::setEnvironment
     * @covers ::addYamlFilenameToIgnore
     * @covers ::setSection
     */
    public function testExtend()
    {
        // test config extend with environment
        $config = new Config($this->test_base_dir);

        $config->setEnvironment('test_extend');
        $config->extend();

        $this->assertEquals($config->getAllSections(), array(
            'say' => array(
                'hello'   => 'world',
                'bonjour' => 'monde',
                'hola'    => 'mundo'
            ),
            'bar' => array(
                'foo' => 'bar',
                'php' => '5.4'
            ),
            'foo' => array(
                'bar'  => 'foo',
                'back' => 'bee'
            )
        ));

        // test config extend with environment and with yml filename ignore
        $config = new Config($this->test_base_dir);

        $config->setEnvironment('test_extend');
        $config->addYamlFilenameToIgnore('bar');
        $config->extend();

        $this->assertEquals($config->getAllSections(), array(
            'say' => array(
                'hello'   => 'world',
                'bonjour' => 'monde',
                'hola'    => 'mundo'
            ),
            'foo' => array(
                'bar'  => 'foo',
                'back' => 'bee'
            )
        ));

        // prepare test extend with and withtout override
        $config = new Config($this->test_base_dir);

        $config->setEnvironment('test_extend');
        $config->extend();

        // test extend WITHOUT override
        $config->extend(realpath($this->test_base_dir . '/test_override'));
        $this->assertEquals($config->getSection('foo'), array(
            'bar'  => null,
            'back' => 'bee',
            'tic'  => 'tac'
        ));

        // test extend WITH override
        $config->extend(realpath($this->test_base_dir . '/test_override'), true);
        $this->assertEquals($config->getSection('foo'), array(
            'bar' => null,
            'tic' => 'tac'
        ));
    }

    /**
     * Test config with container
     *
     * @covers ::setContainer
     * @covers ::getRawSection
     * @covers ::_compileParameters
     * @covers ::_compileAllParameters
     */
    public function testConfigWithContainer()
    {
        $container = new Container();
        $container->setParameter('brand', 'LP Digital');

        $config = new Config(realpath($this->test_base_dir . '/test_container'));
        $this->assertEquals($config->getSection('parameters'), array(
            'brand' => '%brand%'
        ));

        $config->setContainer($container);
        $this->assertEquals($config->getRawSection('parameters'), array(
            'brand' => '%brand%'
        ));

        $this->assertEquals($config->getSection('parameters'), array(
            'brand' => 'LP Digital'
        ));
    }

    /**
     * Test Config class proxy and dump
     *
     * @covers ::getClassProxy
     * @covers ::dump
     */
    public function testConfigDumpable()
    {
        $config = new Config($this->test_base_dir);

        $this->assertEquals(Config::CONFIG_PROXY_CLASSNAME, $config->getClassProxy());
        $config_dump = $config->dump();
        $this->assertTrue(array_key_exists('basedir', $config_dump));
        $this->assertEquals($this->test_base_dir, $config_dump['basedir']);
        $this->assertTrue(array_key_exists('raw_parameters', $config_dump));
        $this->assertEquals(
            array(
                'say' => array(
                    'hello'   => 'world',
                    'bonjour' => 'monde',
                    'hola'    => 'mundo'
                )
            ),
            $config_dump['raw_parameters']
        );
        $this->assertTrue(array_key_exists('environment', $config_dump));
        $this->assertEquals(\BackBuilder\BBApplication::DEFAULT_ENVIRONMENT, $config_dump['environment']);
        $this->assertTrue(array_key_exists('debug', $config_dump));
        $this->assertEquals(false, $config_dump['debug']);
        $this->assertTrue(array_key_exists('yml_names_to_ignore', $config_dump));
        $this->assertEquals(array(), $config_dump['yml_names_to_ignore']);
        $this->assertTrue(array_key_exists('has_cache', $config_dump));
        $this->assertEquals(false, $config_dump['has_cache']);
        $this->assertTrue(array_key_exists('has_container', $config_dump));
        $this->assertEquals(false, $config_dump['has_container']);
    }
}
