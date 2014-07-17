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

use BackBuilder\DependencyInjection\Container;
use BackBuilder\DependencyInjection\Tests\Listener\TestListener;
use BackBuilder\Event\Dispatcher;

use Symfony\Component\DependencyInjection\Definition;

/**
 * Set of tests for BackBuilder\DependencyInjection\Container
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBuilder\DependencyInjection\Container
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    const NEW_DATE_WITH_TAG_VALUE = 8000;

    /**
     * test every new and overrided method provided by BackBuilder\DependencyInjection\Container
     *
     * @covers Container::get
     * @covers Container::getContainerValues
     * @covers Container::_getContainerParameters
     * @covers Container::_getContainerServices
     * @covers Container::isLoaded
     */
    public function testContainerOverridedAndNewMethods()
    {
        // build container to apply every tests on
        $container = new Container();

        // setting random parameters
        $container->setParameter('random_parameter', 'this is a test');

        // creating a definition without any tag (definition of service with id `date_without_tag`)
        $definition = new Definition('DateTime');
        $container->setDefinition('date_without_tag', $definition);

        // creating a definition with one tag, `test` (definition of service with id `date_with_tag`)
        $definition = new Definition('DateTime');
        $definition->addTag('test');
        $container->setDefinition('date_with_tag', $definition);

        // creating a definition for a synthetic service (definition of service with id `synthetic_service`)
        $definition = new Definition();
        $definition->setSynthetic(true);
        $container->setDefinition('synthetic_service', $definition);

        // setting into container a service as listener with id `listener`
        $container->set('listener', new TestListener());

        // creating a new event dispatcher and prepare it for testing the dispatch of event on get of tagged service
        // from the container
        $event_dispatcher = new Dispatcher();
        $event_dispatcher->setContainer($container);
        $event_dispatcher->addListener('service.tagged.test',array('@listener', 'onGetServiceTaggedTestEvent'));
        $container->set('event.dispatcher', $event_dispatcher);

        // basic test for the listener
        $this->assertEquals('bar', $container->get('listener')->getFoo());

        // tests of Container::isLoaded() method
        $this->assertFalse($container->isLoaded('date_without_tag'));
        $container->get('date_without_tag');
        $this->assertTrue($container->isLoaded('date_without_tag'));

        // checks that getting a service without the `test` tag won't change listener's foo value
        $this->assertEquals('bar', $container->get('listener')->getFoo());

        // tests to get a service with tag to check if the value of foo from listener has changed
        // and if the timestamp of the service `date_with_tag` has changed correctly
        $container->get('date_with_tag');
        $this->assertEquals('foo', $container->get('listener')->getFoo());
        $this->assertEquals(self::NEW_DATE_WITH_TAG_VALUE, $container->get('date_with_tag')->getTimestamp());

        // tests that if we get a synthetic service which we didn't define it yet Container::get() will return null
        $this->assertEquals(null, $container->get('synthetic_service'));

        // test for Container::getContainerValues()
        $paramter_key_string = '%random_parameter%';
        $this->assertEquals('this is a test', $container->getContainerValues($paramter_key_string));

        $service_string_id = '@listener';
        $this->assertEquals(
            $container->get('listener')->getFoo(),
            $container->getContainerValues($service_string_id)->getFoo()
        );
    }
}