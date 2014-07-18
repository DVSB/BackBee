<?php
namespace BackBuilder\DependencyInjection\Tests;

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

use BackBuilder\DependencyInjection\ContainerBuilder;
use BackBuilder\Tests\Mock\ManualBBApplication;

/**
 * Set of tests for BackBuilder\DependencyInjection\ContainerBuilder
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBuilder\DependencyInjection\ContainerBuilder
 */
class ContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * base application
     *
     * @var BackBuilder\IApplication
     */
    private $application;

    /**
     * this set of tests resources directory (absolute path)
     *
     * @var string
     */
    private $resources_directory;

    /**
     * setup base of test environment
     */
    public function setUp()
    {
        $this->resources_directory = __DIR__ . '/ContainerBuilderTest_Ressources';

        // create basic manual application without context and environment
        $this->application = new ManualBBApplication();
        $this->application->setBase_Repository($this->resources_directory . '/repository');
        $this->application->setRepository($this->application->getBaseRepository());
        $this->application->setBB_Dir($this->resources_directory . '/backbee');
        $this->application->setConfig_Dir($this->application->getBaseRepository());
        $this->application->setBase_Dir();
    }

    /**
     * @covers ContainerBuilder::getContainer
     * @covers ContainerBuilder::hydrateContainerWithBootstrapParameters
     * @covers ContainerBuilder::tryAddParameter
     * @covers ContainerBuilder::tryParseContainerDump
     * @covers ContainerBuilder::getContainerDumpFilename
     * @covers ContainerBuilder::loadApplicationServices
     * @covers ContainerBuilder::loadLoggerDefinition
     */
    public function testGetContainerWithoutContextAndEnvironment()
    {
        $config_builder = new ContainerBuilder($this->application);

        // test for getContainer()
        $container = $config_builder->getContainer();
        $this->assertTrue(null !== $container);
        $this->assertTrue($container->getParameter('debug', false));
        $this->assertEquals('', $container->getParameter('container.dump_directory', false));
        $this->assertEquals(true, $container->getParameter('container.autogenerate', false));
    }
}
