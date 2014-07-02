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

namespace BackBuilder\Security\Tests\Controller\EventListener;

use BackBuilder\Rest\EventListener\PaginationListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Symfony\Component\HttpFoundation\Request;

use BackBuilder\FrontController\FrontController;

use BackBuilder\Rest\Tests\Fixtures\Controller\AnnotatedController;
/**
 * Test for User class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class PaginationListenerTest extends \PHPUnit_Framework_TestCase
{

    public function testDefaultParams()
    {
        $kernel = new FrontController();
        $controller = array(new AnnotatedController(), 'defaultPaginationAction');
        $request = new Request();
        $request->initialize(array());
        //__construct(HttpKernelInterface $kernel, $controller, Request $request, $requestType)
        $event = new FilterControllerEvent($kernel, $controller, $request, FrontController::MASTER_REQUEST);
        
        
        $listener = new PaginationListener();
        
    }
    
    public function testCustomParams()
    {
        $kernel = new FrontController();
        $controller = array(new AnnotatedController(), 'customPaginationAction');
        $request = new Request();
        $request->initialize(array());
        //__construct(HttpKernelInterface $kernel, $controller, Request $request, $requestType)
        $event = new FilterControllerEvent($kernel, $controller, $request, FrontController::MASTER_REQUEST);
        
        
        $listener = new PaginationListener();
        
    }

}