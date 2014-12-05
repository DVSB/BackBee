<?php

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

namespace BackBuilder\Config\Tests;

use BackBuilder\Config\Config;
use BackBuilder\Config\Configurator;
use BackBuilder\Config\Persistor;
use BackBuilder\Config\Tests\Persistor\FakeContainerBuilder;
use BackBuilder\DependencyInjection\Container;
use BackBuilder\Site\Site;
use BackBuilder\Tests\Mock\ManualBBApplication;
use org\bovigo\vfs\vfsStream;

/**
 * Set of tests for BackBuilder\Config\Persistor
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass BackBuilder\Config\Persistor
 */
class PersistorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BackBuilder\Config\Persistor
     */
    private $persistor;

    /**
     * @var BackBuilder\IApplication
     */
    private $application;

    /**
     * @var BackBuilder\Config\Configurator
     */
    private $configurator;

    /**
     * Create a persistor on which we will apply our tests
     */
    public function setUp()
    {
        $virtual_structure = array(
            'backbee' => array(
                'Config' => array(
                    'config.yml' => \Symfony\Component\Yaml\Yaml::dump(array(
                        'parameters' => array(
                            'hello' => 'world',
                        ),
                    )),
                ),
            ),
            'repository' => array(
                'Config' => array(
                    'config.yml' => \Symfony\Component\Yaml\Yaml::dump(array(
                        'parameters' => array(
                            'hello' => 'backbee',
                        ),
                    )),
                ),
            ),
        );

        vfsStream::setup('virtual_structure', 0777, $virtual_structure);

        // create basic manual application without context and environment
        $application = new ManualBBApplication();
        $application->setBase_Repository(vfsStream::url('virtual_structure').'/repository');
        $application->setRepository($application->getBaseRepository());
        $application->setBB_Dir(vfsStream::url('virtual_structure').'/backbee');
        $application->setConfig_Dir($application->getBBDir().'/Config');
        $application->setBase_Dir(vfsStream::url('virtual_structure'));

        $this->configurator = new Configurator($application);

        $this->persistor = new Persistor($application, $this->configurator);
        $this->application = $application;
        $application->setConfig(new Config($application->getConfigDir()));
        $this->configurator->configureApplicationConfig($application->getConfig());
    }

    /**
     * @covers ::persist
     * @covers ::loadPersistors
     */
    public function test_loadPersistors_RaiseOf_BBException()
    {
        $config = $this->application->getConfig();
        $this->application->setConfig(null);
        try {
            $this->persistor->persist($config);
            $this->fail('Raise of BBException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBuilder\Exception\BBException',
                $e
            );
        }

        $this->application->setConfig($config);
    }

    /**
     * @covers ::persist
     * @covers ::loadPersistors
     */
    public function test_loadPersistors_RaiseOf_PersistorListNotFoundException()
    {
        try {
            $this->persistor->persist($this->application->getConfig());
            $this->fail('Raise of PersistorListNotFoundException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBuilder\Config\Exception\PersistorListNotFoundException',
                $e
            );
        }
    }

    /**
     * @covers ::persist
     * @covers ::loadPersistors
     */
    public function test_loadPersistors_RaiseOf_InvalidArgumentException()
    {
        $this->application->getConfig()->setSection('config', array(
            'persistor' => 'stdClass',
        ));

        try {
            $this->persistor->persist($this->application->getConfig());
            $this->fail('Raise of InvalidArgumentException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBuilder\Exception\InvalidArgumentException',
                $e
            );
        }
    }

    /**
     * @covers ::persist
     * @covers ::loadPersistors
     */
    public function test_updateConfigOverridedSectionsForSite_RaiseOf_BBException()
    {
        $this->application->getConfig()->setSection('config', array(
            'persistor' => 'BackBuilder\Config\Tests\Persistor\FakePersistor',
        ));

        try {
            $this->persistor->persist($this->application->getConfig(), true);
            $this->fail('Raise of BBException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBuilder\Exception\BBException',
                $e
            );
        }
    }

    /**
     * @covers ::persist
     * @covers ::loadPersistors
     * @covers ::doPersist
     */
    public function test_persistWithoutConfigPerSite()
    {
        $this->application->setContainer(new Container());
        $this->application->getContainer()->set('container.builder', new FakeContainerBuilder(true));
        $this->application->getConfig()->setSection('config', array(
            'persistor' => 'BackBuilder\Config\Tests\Persistor\FakePersistor',
        ));

        try {
            $this->persistor->persist($this->application->getConfig());
            $this->fail('Raise of Exception expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                '\Exception',
                $e
            );

            $this->assertEquals(FakeContainerBuilder::MESSAGE, $e->getMessage());
        }
    }

    /**
     * @covers ::persist
     * @covers ::loadPersistors
     * @covers ::doPersist
     * @covers ::updateConfigOverridedSectionsForSite
     */
    public function test_persistWithConfigPerSite()
    {
        $this->application->setIs_Started(true);

        $this->application->getConfig()->setSection('config', array(
            'persistor' => 'BackBuilder\Config\Tests\Persistor\FakePersistor',
        ));

        $this->application->getConfig()->setSection('parameters', array(
            'hello' => 'foo',
        ));

        $this->application->setContainer(new Container());
        $this->application->getContainer()->set('container.builder', new FakeContainerBuilder());
        $current_config = $this->application->getConfig()->getAllRawSections();

        $this->application->setSite($site = new Site());

        $this->persistor->persist($this->application->getConfig(), true);

        $this->assertTrue($this->application->getConfig()->getSection('override_site') !== null);

        $updated_override_section = array(
            'override_site' => array(
                $site->getUid() => array(
                    'parameters' => array(
                        'hello' => 'foo',
                    ),
                    'config' => array(
                        'persistor' => 'BackBuilder\Config\Tests\Persistor\FakePersistor',
                    ),
                ),
            ),
        );

        $this->assertEquals(array_merge(
            $current_config,
            $updated_override_section
        ), $this->application->getConfig()->getAllRawSections());

        $this->assertEquals(array_merge(
            $this->configurator->getConfigDefaultSections($this->application->getConfig()),
            $updated_override_section
        ), $this->application->getContainer()->getParameter('config_to_persist'));
    }
}
