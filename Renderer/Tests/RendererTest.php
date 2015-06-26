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

namespace BackBee\Renderer\Tests;

use BackBee\Renderer\Renderer;
use BackBee\Tests\BackBeeTestCase;

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class RendererTest extends BackBeeTestCase
{
    private static $originalRendererConfig;

    public static function setUpBeforeClass()
    {
        self::$originalRendererConfig = self::$app->getConfig()->getRendererConfig();
    }

    public function testSupportsOfAdaptersConfigOldAndNewFormat()
    {
        $config = self::$app->getConfig();

        $resetAdapterConfig = self::$originalRendererConfig;
        $resetAdapterConfig['adapter'] = [];
        $config->setSection('renderer', $resetAdapterConfig, true);

        $renderer = new Renderer(self::$app);
        $this->assertCount(0, $renderer->getAdapters());

        $oldandNewFormat = self::$originalRendererConfig;
        $oldandNewFormat['adapter'] = [
            'twig' => [
                'class' => 'BackBee\Renderer\Adapter\Twig',
            ],
            'BackBee\Renderer\Adapter\phtml',
        ];

        $config->setSection('renderer', $oldandNewFormat, true);
        $renderer = new Renderer(self::$app);
        $this->assertCount(2, $renderer->getAdapters());
        $this->assertSame('.twig', $renderer->getDefaultAdapterExt());

        $adaptersClass = [
            'twig'  => 'BackBee\Renderer\Adapter\Twig',
            'phtml' => 'BackBee\Renderer\Adapter\phtml',
        ];
        foreach ($renderer->getAdapters() as $key => $adapter) {
            $this->assertInstanceOf($adaptersClass[$key], $adapter);
        }
    }

    public function testPassingAdapterConfig()
    {
        $config = self::$app->getConfig();

        $fakeAdapterConfig = [
            'foo'  => 'bar',
            'fake' => true,
        ];
        $adapterConfig = self::$originalRendererConfig;
        $adapterConfig['adapter'] = [
            'BackBee\Renderer\Adapter\phtml',
            'test' => [
                'class'  => 'BackBee\Renderer\Tests\Mock\FakeRendererAdapter',
                'config' => $fakeAdapterConfig,
            ],
        ];

        $config->setSection('renderer', $adapterConfig, true);
        $renderer = new Renderer(self::$app);
        $this->assertInstanceOf('BackBee\Renderer\Tests\Mock\FakeRendererAdapter', $renderer->getAdapterByExt('test'));
        $this->assertSame($fakeAdapterConfig, $renderer->getAdapterByExt('test')->getAdapterConfig());
    }

    public static function tearDownAfterClass()
    {
        self::$app->getConfig()->setSection('renderer', self::$originalRendererConfig, true);
    }
}
