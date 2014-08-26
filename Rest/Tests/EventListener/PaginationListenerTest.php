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

namespace BackBuilder\Rest\Tests\EventListener;

use BackBuilder\Rest\EventListener\PaginationListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Validator\Validation;

use BackBuilder\FrontController\FrontController;

use BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController;
/**
 * Pagination Listener class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 * 
 * @coversDefaultClass \BackBuilder\Rest\EventListener\PaginationListener
 */
class PaginationListenerTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @covers ::__construct
     * @covers ::getControllerActionMetadata
     */
    public function test__construct()
    {
        $listener = $this->getListener();
        $this->assertInstanceOf('BackBuilder\Rest\EventListener\PaginationListener', $listener);
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
        $this->assertEquals(100, $request->attributes->get('limit'));
    }
    
    /**
     * @covers ::onKernelController
     */
    public function testCustomParams()
    {
        $controller = array(new FixtureAnnotatedController(), 'customPaginationAction');
        
        $request = new Request(array('from' => 220,'max' => 50));
        $event = new FilterControllerEvent(new FrontController(), $controller, $request, FrontController::MASTER_REQUEST);
        
        
        $listener = $this->getListener();
        
        $listener->onKernelController($event);
        
        $this->assertEquals(220, $request->attributes->get('from'));
        $this->assertEquals(50, $request->attributes->get('max'));
    }
    
    /**
     * @expectedException BackBuilder\Rest\Exception\ValidationException
     * @covers ::onKernelController
     */
    public function testInvalidLimitMax()
    {
        $controller = array(new FixtureAnnotatedController(), 'defaultPaginationAction');
        
        $event = new FilterControllerEvent(new FrontController(), $controller, new Request(array(
            'limit' => 1001
        )), FrontController::MASTER_REQUEST);
        
        $listener = $this->getListener();
        
        $listener->onKernelController($event);
    }
    
    /**
     * @expectedException BackBuilder\Rest\Exception\ValidationException
     * @covers ::onKernelController
     */
    public function testInvalidLimitMin()
    {
        $controller = array(new FixtureAnnotatedController(), 'customPaginationAction');
        
        $event = new FilterControllerEvent(new FrontController(), $controller, new Request(array(
            'max' => 5
        )), FrontController::MASTER_REQUEST);
        
        $listener = $this->getListener();
        
        $listener->onKernelController($event);
    }
    
    /**
     * @expectedException BackBuilder\Rest\Exception\ValidationException
     * @covers ::onKernelController
     */
    public function testInvalidStartType()
    {
        $controller = array(new FixtureAnnotatedController(), 'defaultPaginationAction');
        
        $event = new FilterControllerEvent(new FrontController(), $controller, new Request(array(
            'start' => 'string'
        )), FrontController::MASTER_REQUEST);
        
        $listener = $this->getListener();
        
        $listener->onKernelController($event);
    }
    
    /**
     * @expectedException BackBuilder\Rest\Exception\ValidationException
     * @covers ::onKernelController
     */
    public function testInvalidLimitType()
    {
        $controller = array(new FixtureAnnotatedController(), 'defaultPaginationAction');
        
        $event = new FilterControllerEvent(new FrontController(), $controller, new Request(array(
            'limit' => 'string'
        )), FrontController::MASTER_REQUEST);
        
        $listener = $this->getListener();
        
        $listener->onKernelController($event);
    }
    
    protected function getListener()
    {
        $refl = new \ReflectionClass('BackBuilder\Rest\Controller\Annotations\Pagination');
        
        \Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespaces(array(
            'BackBuilder\Rest\Controller\Annotations' => dirname($refl->getFileName()),
        ));
        
        $metadataFactory =  new \Metadata\MetadataFactory(
            new \BackBuilder\Rest\Mapping\Driver\AnnotationDriver(
                new \Doctrine\Common\Annotations\AnnotationReader()
            )
        );
        
        $listener = new PaginationListener($metadataFactory, Validation::createValidator());
        
        return $listener;
    }

}