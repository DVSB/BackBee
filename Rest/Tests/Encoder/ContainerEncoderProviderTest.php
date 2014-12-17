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

namespace BackBee\Rest\Tests\Encoder;

use BackBee\Tests\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use BackBee\Rest\Encoder\ContainerEncoderProvider;

/**
 * Test for Encoder class
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\Encoder\ContainerEncoderProvider
 */
class ContainerEncoderProviderTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function test___construct()
    {
        $container = new \BackBee\DependencyInjection\Container();
        $jsonEncoderId = 'rest.encoder.json';
        $provider = new ContainerEncoderProvider([
            'json' => $jsonEncoderId,
        ]);
        $provider->setContainer($container);

        $this->assertInstanceOf('BackBee\Rest\Encoder\ContainerEncoderProvider', $provider);
    }

    /**
     * @covers ::getEncoder
     */
    public function test_getEncoder()
    {
        $jsonEncoderId = 'rest.encoder.json';
        $container = new \BackBee\DependencyInjection\Container();
        $container->set($jsonEncoderId, new \Symfony\Component\Serializer\Encoder\JsonEncoder());

        $provider = new ContainerEncoderProvider([
            'json' => $jsonEncoderId,
        ]);
        $provider->setContainer($container);

        $encoder = $provider->getEncoder('json');

        $this->assertInstanceOf('Symfony\Component\Serializer\Encoder\JsonEncoder', $encoder);
    }

    /**
     * @covers ::getEncoder
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Format 'xml' is not supported by ContainerDecoderProvider.
     */
    public function test_getEncoder_unsupportedEncoder()
    {
        $jsonEncoderId = 'rest.encoder.json';
        $container = new \BackBee\DependencyInjection\Container();
        $container->set($jsonEncoderId, new \Symfony\Component\Serializer\Encoder\JsonEncoder());

        $provider = new ContainerEncoderProvider([
            'json' => $jsonEncoderId,
        ]);
        $provider->setContainer($container);

        $encoder = $provider->getEncoder('xml');
    }

    /**
     * @covers ::supports
     */
    public function test_supports()
    {
        //Symfony\Component\Serializer\Encoder\EncoderInterface
        //Symfony\Component\Serializer\Encoder\DecoderInterface
        $container = new \BackBee\DependencyInjection\Container();
        $jsonEncoderId = 'unit_test.json_encoder';
        $container->set($jsonEncoderId, new \Symfony\Component\Serializer\Encoder\JsonEncoder());

        $provider = new ContainerEncoderProvider([
            'json' => $jsonEncoderId,
        ]);
        $provider->setContainer($container);

        $this->assertTrue($provider->supports('json'));
        $this->assertFalse($provider->supports('xml'));
    }
}
