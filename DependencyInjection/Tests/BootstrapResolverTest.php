<?php

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

namespace BackBee\DependencyInjection\Tests;

use BackBee\DependencyInjection\BootstrapResolver;

/**
 * Test for BootstrapResolver
 *
 * @category    BackBee
 * @package     BackBee\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class BootstrapResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test resources base directory
     *
     * @var string
     */
    private $resources_base_dir;

    /**
     * define the test resources directory
     */
    public function setUp()
    {
        $this->resources_base_dir = __DIR__.'/BootstrapResolverTest_Resources';
    }

    /**
     * Test BootstrapResolver::getBootstrapPotentialsDirectories(), the number and the order of
     * directory this method returns
     */
    /*public function testGetBootstrapPotentialsDirectories()
    {
        $context = 'api';
        $environment = 'preprod';

        // test WITHOUT context and WITHOUT environment
        $bootstrap_resolver = new BootstrapResolver(__DIR__, null, null);
        $directories = array(
            0 => __DIR__ . DIRECTORY_SEPARATOR . 'Config'
        );

        $this->assertEquals($directories, $bootstrap_resolver->getBootstrapPotentialsDirectories());

        // test WITHOUT context and WITH environment
        $bootstrap_resolver = new BootstrapResolver(__DIR__, null, $environment);
        $directories = array(
            0 => __DIR__ . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . $environment,
            1 => __DIR__ . DIRECTORY_SEPARATOR . 'Config'
        );

        $this->assertEquals($directories, $bootstrap_resolver->getBootstrapPotentialsDirectories());

        // test WITH context and WITHOUT environment
        $bootstrap_resolver = new BootstrapResolver(__DIR__, $context, null);
        $directories = array(
            0 => implode(DIRECTORY_SEPARATOR, array(__DIR__, $context, 'Config')),
            1 => __DIR__ . DIRECTORY_SEPARATOR . 'Config'
        );

        $this->assertEquals($directories, $bootstrap_resolver->getBootstrapPotentialsDirectories());

        // test WITH context and WITH environment
        $bootstrap_resolver = new BootstrapResolver(__DIR__, $context, $environment);
        $directories = array(
            0 => implode(DIRECTORY_SEPARATOR, array(__DIR__, $context, 'Config', $environment)),
            1 => implode(DIRECTORY_SEPARATOR, array(__DIR__, $context, 'Config')),
            2 => __DIR__ . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . $environment,
            3 => __DIR__ . DIRECTORY_SEPARATOR . 'Config'
        );

        $this->assertEquals($directories, $bootstrap_resolver->getBootstrapPotentialsDirectories());
    }*/

    /**
     * test raise of BootstrapFileNotFoundException by providing wrong base directory to BootstrapResolver's
     * constructor
     */
    public function testRaiseBootstrapFileNotFoundException()
    {
        $bootstrap_resolver = new BootstrapResolver($this->resources_base_dir.'/Config', null, null);

        try {
            $bootstrap_resolver->getBootstrapParameters();
            $this->fail('Raise of BootstrapFileNotFoundException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf('BackBee\DependencyInjection\Exception\BootstrapFileNotFoundException', $e);
        }
    }

    /**
     * Test BootstrapResolver::getBootstrapParameters() to check if we get the right parameters from
     * bootstrap.yml depending on context and environment
     */
    public function testGetBootstrapParameters()
    {
        $context = 'api';
        $environment = 'preprod';

        // test to get bootstrap parameters WITHOUT context and WITHOUT environment
        $bootstrap_resolver = new BootstrapResolver($this->resources_base_dir, null, null);
        $this->assertEquals(
            array(
                'context'            => 'default',
                'environment'        => '',
                'bootstrap_filepath' => $this->resources_base_dir.'/Config/bootstrap.yml',
            ),
            $bootstrap_resolver->getBootstrapParameters()
        );

        // test to get bootstrap parameters WITH context and WITH environment
        $bootstrap_resolver = new BootstrapResolver($this->resources_base_dir, $context, $environment);
        $this->assertEquals(
            array(
                'context'            => 'api',
                'environment'        => 'preprod',
                'bootstrap_filepath' => $this->resources_base_dir.'/api/Config/preprod/bootstrap.yml',
            ),
            $bootstrap_resolver->getBootstrapParameters()
        );

        // test to get bootstrap parameters WITH context and WITHOUT environment
        $bootstrap_resolver = new BootstrapResolver($this->resources_base_dir, $context, null);
        $this->assertEquals(
            array(
                'context'            => 'api',
                'environment'        => '',
                'bootstrap_filepath' => $this->resources_base_dir.'/api/Config/bootstrap.yml',
            ),
            $bootstrap_resolver->getBootstrapParameters()
        );

        // test to get bootstrap parameters WITHOUT context and WITH environment
        $bootstrap_resolver = new BootstrapResolver($this->resources_base_dir, null, $environment);
        $this->assertEquals(
            array(
                'context'            => 'default',
                'environment'        => 'preprod',
                'bootstrap_filepath' => $this->resources_base_dir.'/Config/preprod/bootstrap.yml',
            ),
            $bootstrap_resolver->getBootstrapParameters()
        );
    }
}
