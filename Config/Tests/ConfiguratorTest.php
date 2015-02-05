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

namespace BackBee\Config\Tests;

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

use BackBee\Config\Config;
use BackBee\Config\Configurator;
use BackBee\Tests\Mock\ManualBBApplication;

/**
 * Set of tests for BackBee\Config\Configurator
 *
 * @category    BackBee
 * @package     BackBee\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\Config\Configurator
 */
class ConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * mocked application with minimalist properties setted
     *
     * @var BackBee\Tests\Mock\ManualBBApplication
     */
    private $application;

    /**
     * mocked BundleLoader
     *
     * @var BundleLoader
     */
    private $bundleLoader;

    /**
     * setup the test environment with every needed variables
     */
    public function setUp()
    {
        $this->application = new ManualBBApplication();
        $this->bundleLoader = $this->getMockBuilder('BackBee\Bundle\BundleLoader')
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * test extend application config with and without context and environment
     *
     * @covers ::__construct
     * @covers ::extend
     * @covers ::doApplicationConfigExtend
     */
    public function testExtendApplicationConfig()
    {
        $this->application->setBB_Dir(__DIR__.'/ConfiguratorTest_Resources/bbdir');
        $this->application->setBase_Repository(__DIR__.'/ConfiguratorTest_Resources/repository');
        $this->application->setOverrided_Config(false);

        // Test without context and without environment
        $config = new Config($this->application->getBBDir());
        $this->assertEquals(
            array(
                'parameters' => array(
                    'base_directory' => 'bbdir',
                    'context'        => 'default',
                    'environment'    => '',
                ),
            ),
            $config->getAllSections()
        );

        $config_builder = new Configurator($this->application, $this->bundleLoader);
        $config_builder->extend(Configurator::APPLICATION_CONFIG, $config);
        $this->assertEquals(
            array(
                'parameters' => array(
                    'base_directory' => 'repository',
                    'context'        => 'default',
                    'environment'    => '',
                    'foo'            => 'bar',
                ),
            ),
            $config->getAllSections()
        );

        // Test with context and without environment
        $this->application->setContext('api');
        $config = new Config($this->application->getBBDir());
        $config_builder = new Configurator($this->application, $this->bundleLoader);
        $config_builder->extend(Configurator::APPLICATION_CONFIG, $config);
        $this->assertEquals(
            array(
                'parameters' => array(
                    'base_directory' => 'repository',
                    'context'        => 'api',
                    'environment'    => '',
                    'foo'            => 'bar',
                    'bar'            => 'foo',
                ),
            ),
            $config->getAllSections()
        );

        // Test with context and with environment; test also with override config setted at true
        $this->application->setContext('api');
        $this->application->setEnvironment('preprod');
        $this->application->setOverrided_Config(true);
        $config = new Config($this->application->getBBDir());
        $config_builder = new Configurator($this->application, $this->bundleLoader);
        $config_builder->extend(Configurator::APPLICATION_CONFIG, $config);
        $this->assertEquals(
            array(
                'parameters' => array(
                    'context'          => 'api',
                    'environment'      => 'preprod',
                    'overrided_config' => true,
                ),
            ),
            $config->getAllSections()
        );
    }
}
