<?php
namespace BackBuilder\DependencyInjection\Tests\Listener;

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
use BackBuilder\DependencyInjection\Listener\ContainerListener;
use BackBuilder\Event\Event;
use BackBuilder\Tests\Mock\ManualBBApplication;

use org\bovigo\vfs\vfsStream;

/**
 * Set of tests for BackBuilder\DependencyInjection\Listener\ContainerListener
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBuilder\DependencyInjection\Listener\ContainerListener
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test every new and overrided method provided by BackBuilder\DependencyInjection\Container
     *
     * @covers ContainerListener::onApplicationInit
     */
    public function testOnApplicationInitWithDebugTrue()
    {
        $resources_directory = realpath(__DIR__ . '/../ContainerBuilderTest_Ressources/');
        $virtual_structure = array(
            'backbee' => array(
                'Config' => array(
                    'services.yml' => file_get_contents($resources_directory . '/backbee/Config/services.yml')
                )
            ),
            'repository' => array(
                'Config' => array(
                    'services.yml' => file_get_contents($resources_directory . '/repository/Config/services.yml'),
                    'bootstrap.yml' => file_get_contents($resources_directory . '/repository/Config/bootstrap.yml')
                ),
            ),
            'container' => array()
        );

        vfsStream::setup('full_right_base_directory', 0777, $virtual_structure);

        $application = $this->generateManualBBApplication(vfsStream::url('full_right_base_directory'));
        $application->setContainer((new ContainerBuilder($application))->getContainer());

        $container = $application->getContainer();
        $this->assertInstanceOf('BackBuilder\DependencyInjection\ContainerInterface', $container);

        $container->setParameter('container.dump_directory', $application->getBaseDir() . '/container');

        $this->assertFalse($container->isFrozen());
        $event = new Event($application);
        ContainerListener::onApplicationInit($event);
        $this->assertTrue($container->isFrozen());

        $this->assertFileNotExists(implode(DIRECTORY_SEPARATOR, array(
            $container->getParameter('container.dump_directory'),
            $container->getParameter('container.filename')
        )));
    }

    public function testOnApplicationInitRaisesCannotCreateContainerDirectoryException()
    {
        $basic_services_yml = array(
            'parameters' => array(
                'bbapp.logger.class'       => 'BackBuilder\Logging\Logger',
                'bbapp.logger_debug.class' => 'BackBuilder\Logging\DebugStackLogger'
            )
        );

        $bootstrap_yml = array(
            'debug'     => false,
            'container' => array(
                'dump_directory' => 'vfs://no_rights_base_directory/container',
                'autogenerate'   => true
            )
        );

        $resources_directory = realpath(__DIR__ . '/../ContainerBuilderTest_Ressources/');
        $virtual_structure = array(
            'backbee' => array(
                'Config' => array(
                    'services.yml' => \Symfony\Component\Yaml\Yaml::dump($basic_services_yml)
                )
            ),
            'repository' => array(
                'Config' => array(
                    'bootstrap.yml' => \Symfony\Component\Yaml\Yaml::dump($bootstrap_yml)
                ),
            )
        );

        vfsStream::setup('no_rights_base_directory', 0000, $virtual_structure);

        $application = $this->generateManualBBApplication(vfsStream::url('no_rights_base_directory'));
        $application->setContainer((new ContainerBuilder($application))->getContainer());

        $container = $application->getContainer();
        $application->setDebug_Mode(false);
        $container->setParameter('container.dump_directory', $application->getBaseDir() . '/container');

        $this->assertFalse($container->isFrozen());
        $event = new Event($application);
        try {
            ContainerListener::onApplicationInit($event);
            $this->fail('Raise of CannotCreateContainerDirectoryException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBuilder\DependencyInjection\Exception\CannotCreateContainerDirectoryException',
                $e
            );
        }
    }

    // public function testOnApplicationInitRaisesContainerDirectoryNotWritableException()
    // {
    //     $basic_services_yml = array(
    //         'parameters' => array(
    //             'bbapp.logger.class'       => 'BackBuilder\Logging\Logger',
    //             'bbapp.logger_debug.class' => 'BackBuilder\Logging\DebugStackLogger'
    //         )
    //     );

    //     $bootstrap_yml = array(
    //         'debug'     => false,
    //         'container' => array(
    //             'dump_directory' => 'vfs://no_rights_base_directory/container',
    //             'autogenerate'   => true
    //         )
    //     );

    //     $resources_directory = realpath(__DIR__ . '/../ContainerBuilderTest_Ressources/');
    //     $virtual_structure = array(
    //         'backbee' => array(
    //             'Config' => array(
    //                 'services.yml' => \Symfony\Component\Yaml\Yaml::dump($basic_services_yml)
    //             )
    //         ),
    //         'repository' => array(
    //             'Config' => array(
    //                 'bootstrap.yml' => \Symfony\Component\Yaml\Yaml::dump($bootstrap_yml)
    //             ),
    //         ),
    //         'container' => array()
    //     );

    //     vfsStream::setup('cant_write_base_directory', 0000, $virtual_structure);

    //     $application = $this->generateManualBBApplication(vfsStream::url('cant_write_base_directory'));
    //     $application->setContainer((new ContainerBuilder($application))->getContainer());

    //     $container = $application->getContainer();
    //     $application->setDebug_Mode(false);
    //     $container->setParameter('container.dump_directory', $application->getBaseDir() . '/container');

    //     $this->assertFalse($container->isFrozen());
    //     $event = new Event($application);
    //     try {
    //         ContainerListener::onApplicationInit($event);
    //         $this->fail('Raise of ContainerDirectoryNotWritableException expected.');
    //     } catch (\Exception $e) {
    //         var_dump(get_class($e));
    //         $this->assertInstanceOf(
    //             'BackBuilder\DependencyInjection\Exception\ContainerDirectoryNotWritableException',
    //             $e
    //         );
    //     }
    // }

    private function generateManualBBApplication($base_directory)
    {
        $application = new ManualBBApplication();
        $application->setBase_Repository($base_directory . '/repository');
        $application->setRepository($application->getBaseRepository());
        $application->setBB_Dir($base_directory . '/backbee');
        $application->setConfig_Dir($application->getBaseRepository());
        $application->setBase_Dir($base_directory);

        return $application;
    }
}
