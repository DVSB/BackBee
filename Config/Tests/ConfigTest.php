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
 * A set of configuration parameters store in a yaml file
 * The parameters had to be filtered by section
 * Note that parameters and services will be set only if setContainer() is called
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBuilder\Config\Config
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test the Config constructor
     *
     * @covers Config::extend
     * @covers Config::_loadFromBaseDir
     * @covers Config::_getYmlFiles
     * @covers Config::_loadFromFile
     * @covers Config::hasSection
     * @covers Config::sectionHasKey
     * @covers Config::getSection
     * @covers Config::getBaseDir
     */
    public function testConstruct()
    {
        $config = new Config(__DIR__);

        $this->assertEquals(__DIR__, $config->getBaseDir());
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
     * @covers Config::extend
     * @covers Config::setEnvironment
     * @covers Config::addYamlFilenameToIgnore
     * @covers Config::setSection
     */
    public function testExtend()
    {
        // test config extend with environment
        $config = new Config(__DIR__);

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
        $config = new Config(__DIR__);

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
        $config = new Config(__DIR__);

        $config->setEnvironment('test_extend');
        $config->extend();

        // test extend WITHOUT override
        $config->extend(realpath(__DIR__ . '/test_override'));
        $this->assertEquals($config->getSection('foo'), array(
            'bar'  => null,
            'back' => 'bee',
            'tic'  => 'tac'
        ));

        // test extend WITH override
        $config->extend(realpath(__DIR__ . '/test_override'), true);
        $this->assertEquals($config->getSection('foo'), array(
            'bar' => null,
            'tic' => 'tac'
        ));
    }

    /**
     * Test config with container
     *
     * @covers Config::setContainer
     * @covers Config::getRawSection
     * @covers Config::_compileParameters
     * @covers Config::_compileAllParameters
     */
    public function testConfigWithContainer()
    {
        $container = new Container();
        $container->setParameter('brand', 'LP Digital');

        $config = new Config(realpath(__DIR__) . '/test_container');
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
}
