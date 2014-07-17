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
use BackBuilder\Config\ConfigBuilder;
use BackBuilder\Tests\Mock\ManualBBApplication;

/**
 * Set of tests for BackBuilder\Config\ConfigBuilder
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBuilder\Config\ConfigBuilder
 */
class ConfigBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * mocked application with minimalist properties setted
     *
     * @var BackBuilder\Tests\Mock\ManualBBApplication
     */
    private $application;

    /**
     * the ConfigBuilder we want to test
     *
     * @var BackBuilder\Config\ConfigBuilder
     */
    private $config_builder;

    /**
     * setup the test environment with every needed variables
     */
    public function setUp()
    {
        $this->application = new ManualBBApplication();
    }

    /**
     * test extend application config with and without context and environment
     *
     * @covers ConfigBuilder::__construct
     * @covers ConfigBuilder::extend
     * @covers ConfigBuilder::doApplicationConfigExtend
     */
    public function testExtendApplicationConfig()
    {
        $this->application->setBB_Dir(__DIR__ . '/ConfigBuilderTest_Resources/bbdir');
        $this->application->setBase_Repository(__DIR__ . '/ConfigBuilderTest_Resources/repository');
        $this->application->setOverrided_Config(false);

        // Test without context and without environment
        $config = new Config($this->application->getBBDir());
        $this->assertEquals(
            array(
                'parameters' => array(
                    'base_directory' => 'bbdir',
                    'context'        => 'default',
                    'environment'    => ''
                )
            ),
            $config->getAllSections()
        );

        $config_builder = new ConfigBuilder($this->application);
        $config_builder->extend(ConfigBuilder::APPLICATION_CONFIG, $config);
        $this->assertEquals(
            array(
                'parameters' => array(
                    'base_directory' => 'repository',
                    'context'        => 'default',
                    'environment'    => '',
                    'foo'            => 'bar'
                )
            ),
            $config->getAllSections()
        );

        // Test with context and without environment
        $this->application->setContext('api');
        $config = new Config($this->application->getBBDir());
        $config_builder = new ConfigBuilder($this->application);
        $config_builder->extend(ConfigBuilder::APPLICATION_CONFIG, $config);
        $this->assertEquals(
            array(
                'parameters' => array(
                    'base_directory' => 'repository',
                    'context'        => 'api',
                    'environment'    => '',
                    'foo'            => 'bar',
                    'bar'            => 'foo'
                )
            ),
            $config->getAllSections()
        );

        // Test with context and with environment; test also with override config setted at true
        $this->application->setContext('api');
        $this->application->setEnvironment('preprod');
        $this->application->setOverrided_Config(true);
        $config = new Config($this->application->getBBDir());
        $config_builder = new ConfigBuilder($this->application);
        $config_builder->extend(ConfigBuilder::APPLICATION_CONFIG, $config);
        $this->assertEquals(
            array(
                'parameters' => array(
                    'context'          => 'api',
                    'environment'      => 'preprod',
                    'overrided_config' => true
                )
            ),
            $config->getAllSections()
        );
    }
}
