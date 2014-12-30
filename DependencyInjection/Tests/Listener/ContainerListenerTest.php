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

namespace BackBee\DependencyInjection\Tests\Listener;

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

use org\bovigo\vfs\vfsStream;

use BackBee\DependencyInjection\ContainerBuilder;
use BackBee\DependencyInjection\Listener\ContainerListener;
use BackBee\Event\Event;
use BackBee\Tests\Mock\ManualBBApplication;

/**
 * Set of tests for BackBee\DependencyInjection\Listener\ContainerListener
 *
 * @category    BackBee
 * @package     BackBee\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\DependencyInjection\Listener\ContainerListener
 */
class ContainerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test every new and overrided method provided by BackBee\DependencyInjection\Container
     *
     * @covers ::onApplicationInit
     */
    public function testOnApplicationInitWithDebugTrue()
    {
        $resources_directory = realpath(__DIR__.'/../ContainerBuilderTest_Resources/');
        $virtual_structure = array(
            'backbee' => array(
                'Config' => array(
                    'services' => array(
                        'services.yml' => file_get_contents($resources_directory.'/backbee/Config/services/services.yml'),
                    ),
                ),
            ),
            'repository' => array(
                'Config' => array(
                    'services.yml' => file_get_contents($resources_directory.'/repository/Config/services.yml'),
                    'bootstrap.yml' => file_get_contents($resources_directory.'/repository/Config/bootstrap.yml'),
                ),
            ),
            'container' => array(),
        );

        vfsStream::setup('full_right_base_directory', 0777, $virtual_structure);

        $application = $this->generateManualBBApplication(vfsStream::url('full_right_base_directory'));
        $application->setDebug_Mode(true);
        $application->setContainer((new ContainerBuilder($application))->getContainer());

        $container = $application->getContainer();
        $this->assertInstanceOf('BackBee\DependencyInjection\ContainerInterface', $container);

        $container->setParameter('container.dump_directory', $application->getBaseDir().'/container');

        $this->assertFalse($container->isFrozen());
        $event = new Event($application);
        ContainerListener::onApplicationInit($event);
        $this->assertTrue($container->isFrozen());

        $this->assertFileNotExists(implode(DIRECTORY_SEPARATOR, array(
            $container->getParameter('container.dump_directory'),
            $container->getParameter('container.filename'),
        )));
    }

    public function testOnApplicationInitRaisesCannotCreateContainerDirectoryException()
    {
        $basic_services_yml = array(
            'parameters' => array(
                'bbapp.logger.class'       => 'BackBee\Logging\Logger',
                'bbapp.logger_debug.class' => 'BackBee\Logging\DebugStackLogger',
            ),
        );

        $bootstrap_yml = array(
            'debug'     => false,
            'container' => array(
                'dump_directory' => 'vfs://no_rights_base_directory/container',
                'autogenerate'   => true,
            ),
        );

        $resources_directory = realpath(__DIR__.'/../ContainerBuilderTest_Ressources/');
        $virtual_structure = array(
            'backbee' => array(
                'Config' => array(
                    'services' => array(
                        'services.yml' => \Symfony\Component\Yaml\Yaml::dump($basic_services_yml),
                    ),
                ),
            ),
            'repository' => array(
                'Config' => array(
                    'bootstrap.yml' => \Symfony\Component\Yaml\Yaml::dump($bootstrap_yml),
                ),
            ),
        );

        vfsStream::setup('no_rights_base_directory', 0444, $virtual_structure);

        $application = $this->generateManualBBApplication(vfsStream::url('no_rights_base_directory'));
        $application->setContainer((new ContainerBuilder($application))->getContainer());

        $container = $application->getContainer();
        $application->setDebug_Mode(false);
        $container->setParameter('container.dump_directory', $application->getBaseDir().'/container');

        $this->assertFalse($container->isFrozen());
        $event = new Event($application);
        try {
            ContainerListener::onApplicationInit($event);
            $this->fail('Raise of CannotCreateContainerDirectoryException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBee\DependencyInjection\Exception\CannotCreateContainerDirectoryException',
                $e
            );
        }
    }

    public function testOnApplicationInitRaisesContainerDirectoryNotWritableException()
    {
        $basic_services_yml = array(
            'parameters' => array(
                'bbapp.logger.class'       => 'BackBee\Logging\Logger',
                'bbapp.logger_debug.class' => 'BackBee\Logging\DebugStackLogger',
            ),
        );

        $bootstrap_yml = array(
            'debug'     => false,
            'container' => array(
                'dump_directory' => 'vfs://no_rights_base_directory/container',
                'autogenerate'   => true,
            ),
        );

        $resources_directory = realpath(__DIR__.'/../ContainerBuilderTest_Ressources/');
        $virtual_structure = array(
            'backbee' => array(
                'Config' => array(
                    'services' => array(
                        'services.yml' => \Symfony\Component\Yaml\Yaml::dump($basic_services_yml),
                    ),
                ),
            ),
            'repository' => array(
                'Config' => array(
                    'bootstrap.yml' => \Symfony\Component\Yaml\Yaml::dump($bootstrap_yml),
                ),
            ),
            'container' => array(),
        );

        vfsStream::umask(0222);
        vfsStream::setup('cant_write_base_directory', 0777, $virtual_structure);

        $application = $this->generateManualBBApplication(vfsStream::url('cant_write_base_directory'));
        $application->setContainer((new ContainerBuilder($application))->getContainer());

        $container = $application->getContainer();
        $application->setDebug_Mode(false);
        $container->setParameter('container.dump_directory', $application->getBaseDir().'/container');

        $this->assertFalse($container->isFrozen());
        $event = new Event($application);
        try {
            ContainerListener::onApplicationInit($event);
            $this->fail('Raise of ContainerDirectoryNotWritableException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBee\DependencyInjection\Exception\ContainerDirectoryNotWritableException',
                $e
            );
        }
    }

    private function generateManualBBApplication($base_directory)
    {
        $application = new ManualBBApplication();
        $application->setBase_Repository($base_directory.'/repository');
        $application->setRepository($application->getBaseRepository());
        $application->setBB_Dir($base_directory.'/backbee');
        $application->setConfig_Dir($application->getBaseRepository());
        $application->setBase_Dir($base_directory);

        return $application;
    }
}
