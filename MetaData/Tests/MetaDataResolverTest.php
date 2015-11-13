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

namespace BackBee\NestedNode\Tests;

use BackBee\ClassContent\Tests\Mock\MockContent;
use BackBee\MetaData\MetaDataResolver;
use BackBee\NestedNode\Page;
use BackBee\Tests\BackBeeTestCase;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MetaDataResolverTest extends BackBeeTestCase
{

    /**
     * The tested resolver.
     *
     * @var MetaDataResolver
     */
    private $resolver;

    public static function setUpBeforeClass()
    {
        $metadata = [
            'title' => [
                'name' => 'title',
                'content' => [
                    'default' => 'default title',
                    'layout' => [
                        'layout-test-uid' => '$ContentSet[0]->$Tests/Mock/MockContent[0]->$title'
                    ],
                ],
            ],
            'description' => [
                'name' => 'description',
                'content' => [
                    'default' => 'default description',
                    'layout' => [
                        'layout-test-label' => '$ContentSet[0]->$Tests/Mock/MockContent[0]->$title'
                    ],
                ],
            ],
            'robots' => [
                'name' => 'robots',
                'content' => 'follow',
            ],
            'og-url' => [
                'name' => 'og-url',
                'content' => '%url',
            ],
        ];

        self::$kernel->getApplication()->getConfig()->setSection('metadata', $metadata, true);
    }

    public function testResolveWithoutDefinition()
    {
        $resolver = new MetaDataResolver();
        $bag = $resolver->resolve();

        $this->assertInstanceOf('BackBee\MetaData\MetaDataBag', $bag);
        $this->assertEquals(0, $bag->count());
    }

    public function testResolveWithoutPage()
    {
        $bag = $this->resolver->resolve();

        $expected = [
            'title' => ['content' => 'default title'],
            'description' => ['content' => 'default description'],
            'robots' => ['content' => 'follow'],
            'og-url' => ['content' => ''],
        ];

        $this->assertInstanceOf('BackBee\MetaData\MetaDataBag', $bag);
        $this->assertEquals($expected, $bag->jsonSerialize());
    }

    public function testResolveWithUnknownLayout()
    {
        $content = new MockContent();
        $content->title->value = 'content title';

        $layout = self::$kernel->createLayout('unknown-label', 'unknown-uid');
        $page = new Page();
        $page->setLayout($layout, $content);

        $bag = $this->resolver->resolve($page);

        $expected = [
            'title' => ['content' => 'default title'],
            'description' => ['content' => 'default description'],
            'robots' => ['content' => 'follow'],
            'og-url' => ['content' => ''],
        ];

        $this->assertInstanceOf('BackBee\MetaData\MetaDataBag', $bag);
        $this->assertEquals($expected, $bag->jsonSerialize());
    }

    public function testResolveWithKnownLayout()
    {
        $content = new MockContent();
        $content->title->value = 'content title';

        $layout = self::$kernel->createLayout('layout-test-label', 'layout-test-uid');
        $page = new Page();
        $page->setLayout($layout, $content);

        $bag = $this->resolver->resolve($page);

        $expected = [
            'title' => ['content' => 'content title'],
            'description' => ['content' => 'content title'],
            'robots' => ['content' => 'follow'],
            'og-url' => ['content' => ''],
        ];

        $this->assertInstanceOf('BackBee\MetaData\MetaDataBag', $bag);
        $this->assertEquals($expected, $bag->jsonSerialize());
        $this->assertEquals($page, $content->title->getMainNode());
    }

    public function testResolvePageConst()
    {
        $page = new Page();
        $page->setUrl('/page-url');

        $bag = $this->resolver->resolve($page);

        $expected = [
            'title' => ['content' => 'default title'],
            'description' => ['content' => 'default description'],
            'robots' => ['content' => 'follow'],
            'og-url' => ['content' => '/page-url'],
        ];

        $this->assertInstanceOf('BackBee\MetaData\MetaDataBag', $bag);
        $this->assertEquals($expected, $bag->jsonSerialize());
    }

    /**
     * Sets up the fixture.
     */
    protected function setUp()
    {
        $this->resolver = new MetaDataResolver();
        $this->resolver->setDefinitionsFromConfig(self::$kernel->getApplication()->getConfig());
    }

}
