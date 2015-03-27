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

namespace BackBee\Rest\Tests\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Validator\Validation;
use BackBee\FrontController\FrontController;
use BackBee\Rest\EventListener\PaginationListener;
use BackBee\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController;
use BackBee\Tests\TestCase;

/**
 * Pagination Listener class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\EventListener\PaginationListener
 */
class PaginationListenerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        // init bbapp to enable autoloading of annotations
        $this->getBBApp();
    }

    /**
     * @covers ::__construct
     * @covers ::getControllerActionMetadata
     */
    public function test__construct()
    {
        $listener = $this->getListener();
        $this->assertInstanceOf('BackBee\Rest\EventListener\PaginationListener', $listener);
    }

    /**
     * @covers ::onKernelController
     * @covers ::getControllerActionMetadata
     */
    public function testDefaultParams()
    {
        $controller = array(new FixtureAnnotatedController(), 'defaultPaginationAction');
        $request = new Request(array());
        $event = new FilterControllerEvent(new FrontController(), $controller, $request, FrontController::MASTER_REQUEST);

        $listener = $this->getListener();
        $listener->onKernelController($event);

        $this->assertEquals(0, $request->attributes->get('start'));
        $this->assertEquals(100, $request->attributes->get('count'));
    }

    /**
     * @covers ::onKernelController
     */
    public function testCustomParams()
    {
        $controller = array(new FixtureAnnotatedController(), 'customPaginationAction');

        $request = new Request();
        $request->headers->set('Range', '220,50');
        $event = new FilterControllerEvent(new FrontController(), $controller, $request, FrontController::MASTER_REQUEST);

        $listener = $this->getListener();

        $listener->onKernelController($event);

        $this->assertEquals(220, $request->attributes->get('start'));
        $this->assertEquals(50, $request->attributes->get('count'));
    }

    /**
     * @expectedException BackBee\Rest\Exception\ValidationException
     * @covers ::onKernelController
     */
    public function testInvalidLimitMax()
    {
        $controller = array(new FixtureAnnotatedController(), 'defaultPaginationAction');

        $request = new Request();
        $request->headers->set('Range', '0,1001');
        $event = new FilterControllerEvent(new FrontController(), $controller, $request, FrontController::MASTER_REQUEST);

        $listener = $this->getListener();

        $listener->onKernelController($event);
    }

    /**
     * @expectedException BackBee\Rest\Exception\ValidationException
     * @covers ::onKernelController
     */
    public function testInvalidLimitMin()
    {
        $controller = array(new FixtureAnnotatedController(), 'customPaginationAction');

        $request = new Request();
        $request->headers->set('Range', '0,5');
        $event = new FilterControllerEvent(new FrontController(), $controller, $request, FrontController::MASTER_REQUEST);

        $listener = $this->getListener();

        $listener->onKernelController($event);
    }

    protected function getListener()
    {
        $refl = new \ReflectionClass('BackBee\Rest\Controller\Annotations\Pagination');

        \Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespaces(array(
            'BackBee\Rest\Controller\Annotations' => dirname($refl->getFileName()),
        ));

        $metadataFactory =  new \Metadata\MetadataFactory(
            new \BackBee\Rest\Mapping\Driver\AnnotationDriver(
                new \Doctrine\Common\Annotations\AnnotationReader()
            )
        );

        $listener = new PaginationListener($metadataFactory, Validation::createValidator());

        return $listener;
    }
}
