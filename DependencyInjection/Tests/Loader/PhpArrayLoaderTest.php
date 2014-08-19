<?php
namespace BackBuilder\DependencyInjection\Tests\Loader;

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

use BackBuilder\DependencyInjection\Container;
use BackBuilder\DependencyInjection\Loader\PhpArrayLoader;

use org\bovigo\vfs\vfsStream;

/**
 * Test for PhpArrayLoader
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBuilder\DependencyInjection\Loader\PhpArrayLoader
 */
class PhpArrayLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * the container used for test
     *
     * @var BackBuilder\DependencyInjection\Container
     */
    private $container;

    /**
     * setup base of test environment
     */
    public function setUp()
    {
        $this->container = new Container();
    }

    /**
     * @covers ::__construct
     * @covers ::load
     */
    public function testLoad()
    {
        vfsStream::setup('container_dump_directory', 0777, array(
            'container_valid_dump' => serialize(array(
                'parameters' => array(
                    'foo' => 'bar'
                ),
                'services' => array(
                    'datetime' => array(
                        'class' => '\DateTime'
                    )
                ),
                'aliases' => array(),
                'services_dump' => array(),
                'is_compiled' => false
            ))
        ));

        $container_loader = new PhpArrayLoader($this->container);
        $container_loader->load(vfsStream::url('container_dump_directory') . '/container_valid_dump');
        $this->assertInstanceOf('BackBuilder\DependencyInjection\Loader\ContainerProxy', $this->container);
        $this->assertInstanceOf('DateTime', $this->container->get('datetime'));
        $this->assertEquals('bar', $this->container->getParameter('foo'));
    }

    /**
     * @covers ::__construct
     * @covers ::load
     */
    public function testRaiseOfInvalidContainerDumpFilePathException()
    {
        vfsStream::umask(0444);
        vfsStream::setup('container_dump_directory', 0777, array(
            'cant_read_container_dump' => ''
        ));

        $container_loader = new PhpArrayLoader($this->container);

        try {
            $container_loader->load(vfsStream::url('container_dump_directory') . '/cant_read_container_dump');
            $this->fail('Raise of InvalidContainerDumpFilePathException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBuilder\DependencyInjection\Exception\InvalidContainerDumpFilePathException',
                $e
            );
        }
    }

    /**
     * @covers ::__construct
     * @covers ::load
     */
    public function testRaiseOfContainerDumpInvalidFormatException()
    {
        vfsStream::umask(0);
        vfsStream::setup('container_dump_directory', 0777, array(
            'container_dump_invalid_format' => ''
        ));

        $container_loader = new PhpArrayLoader($this->container);

        try {
            $container_loader->load(vfsStream::url('container_dump_directory') . '/container_dump_invalid_format');
            $this->fail('Raise of ContainerDumpInvalidFormatException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBuilder\DependencyInjection\Exception\ContainerDumpInvalidFormatException',
                $e
            );
        }
    }

    /**
     * @covers ::__construct
     * @covers ::load
     */
    public function testMissingParametersContainerDumpException()
    {
        vfsStream::setup('container_dump_directory', 0777, array(
            'container_dump_missing_parameters' => 'a:0:{}'
        ));

        $container_loader = new PhpArrayLoader($this->container);

        try {
            $container_loader->load(vfsStream::url('container_dump_directory') . '/container_dump_missing_parameters');
            $this->fail('Raise of MissingParametersContainerDumpException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                'BackBuilder\DependencyInjection\Exception\MissingParametersContainerDumpException',
                $e
            );
        }
    }
}
