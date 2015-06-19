<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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

namespace BackBee\DependencyInjection\Tests;

use BackBee\DependencyInjection\BootstrapResolver;

/**
 * Test for BootstrapResolver.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class BootstrapResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test resources base directory.
     *
     * @var string
     */
    private $resourcesBaseDir;

    /**
     * define the test resources directory.
     */
    public function setUp()
    {
        $this->resourcesBaseDir = __DIR__.'/BootstrapResolverTest_Resources';
    }

    /**
     * test raise of BootstrapFileNotFoundException by providing wrong base directory to BootstrapResolver's
     * constructor.
     */
    public function testRaiseBootstrapFileNotFoundException()
    {
        $bootstrap_resolver = new BootstrapResolver($this->resourcesBaseDir.'/Config', null, null);

        try {
            $bootstrap_resolver->getBootstrapParameters();
            $this->fail('Raise of BootstrapFileNotFoundException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf('BackBee\DependencyInjection\Exception\BootstrapFileNotFoundException', $e);
        }
    }

    /**
     * Test BootstrapResolver::getBootstrapParameters() to check if we get the right parameters from
     * bootstrap.yml depending on context and environment.
     */
    public function testGetBootstrapParameters()
    {
        $context = 'api';
        $environment = 'preprod';

        // test to get bootstrap parameters WITHOUT context and WITHOUT environment
        $bootstrap_resolver = new BootstrapResolver($this->resourcesBaseDir, null, null);
        $this->assertEquals(
            array(
                'context'            => 'default',
                'environment'        => '',
                'bootstrap_filepath' => $this->resourcesBaseDir.'/Config/bootstrap.yml',
            ),
            $bootstrap_resolver->getBootstrapParameters()
        );

        // test to get bootstrap parameters WITH context and WITH environment
        $bootstrap_resolver = new BootstrapResolver($this->resourcesBaseDir, $context, $environment);
        $this->assertEquals(
            array(
                'context'            => 'api',
                'environment'        => 'preprod',
                'bootstrap_filepath' => $this->resourcesBaseDir.'/api/Config/preprod/bootstrap.yml',
            ),
            $bootstrap_resolver->getBootstrapParameters()
        );

        // test to get bootstrap parameters WITH context and WITHOUT environment
        $bootstrap_resolver = new BootstrapResolver($this->resourcesBaseDir, $context, null);
        $this->assertEquals(
            array(
                'context'            => 'api',
                'environment'        => '',
                'bootstrap_filepath' => $this->resourcesBaseDir.'/api/Config/bootstrap.yml',
            ),
            $bootstrap_resolver->getBootstrapParameters()
        );

        // test to get bootstrap parameters WITHOUT context and WITH environment
        $bootstrap_resolver = new BootstrapResolver($this->resourcesBaseDir, null, $environment);
        $this->assertEquals(
            array(
                'context'            => 'default',
                'environment'        => 'preprod',
                'bootstrap_filepath' => $this->resourcesBaseDir.'/Config/preprod/bootstrap.yml',
            ),
            $bootstrap_resolver->getBootstrapParameters()
        );
    }
}
