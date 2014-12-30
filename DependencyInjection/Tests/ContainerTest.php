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

namespace BackBee\DependencyInjection\Tests;

use Symfony\Component\DependencyInjection\Definition;

use BackBee\DependencyInjection\Container;
use BackBee\DependencyInjection\Tests\ContainerTest_Resources\Listener\TestListener;
use BackBee\Event\Dispatcher;

/**
 * Set of tests for BackBee\DependencyInjection\Container
 *
 * @category    BackBee
 * @package     BackBee\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\DependencyInjection\Container
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    const NEW_DATE_WITH_TAG_VALUE = 8000;

    /**
     * test every new and overrided method provided by BackBee\DependencyInjection\Container
     *
     * @covers ::get
     * @covers ::getContainerValues
     * @covers ::_getContainerParameters
     * @covers ::_getContainerServices
     * @covers ::isLoaded
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
        $definition = new Definition('BackBee\DependencyInjection\Tests\ContainerTest_Resources\DateTime');
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
        $event_dispatcher->addListener('service.tagged.test', array('@listener', 'onGetServiceTaggedTestEvent'));
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

        // Test that there is no events to dispatch if the tag name is `dumpable`
        $container->set('listener', new TestListener());
        $event_dispatcher->addListener('service.tagged.dumpable', array('@listener', 'onGetServiceTaggedTestEvent'));

        $definition = new Definition('DateTime');
        $definition->addTag('dumpable', array('dispatch_event' => false));
        $container->setDefinition('dumpable_date', $definition);

        $container->get('dumpable_date');
        $this->assertEquals('bar', $container->get('listener')->getFoo());
    }
}
