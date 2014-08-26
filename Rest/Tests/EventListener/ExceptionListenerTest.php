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

use BackBuilder\Tests\TestCase;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

use BackBuilder\Rest\EventListener\ExceptionListener,
    BackBuilder\FrontController\FrontController;


/**
 * Test for ExceptionListener class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 * 
 * @coversDefaultClass \BackBuilder\Rest\EventListener\ExceptionListener
 */
class ExceptionListenerTest extends TestCase
{
    
    /**
     * @covers ::onKernelException
     * @covers ::setMapping
     */
    public function test_onKernelException_exceptionMapping()
    {
        $listener = new ExceptionListener();
        $event = new GetResponseForExceptionEvent(
            $this->getBBApp()->getController(), 
            new Request(), 
            FrontController::MASTER_REQUEST, 
            new \InvalidArgumentException('Invalid Argument')
        );
        
        
        $listener->setMapping([]);
        $listener->onKernelException($event);
        $this->assertEquals(null, $event->getResponse());
        

        $listener->setMapping([
            'InvalidArgumentException' => [
                'code' => 501,
                'message' => 'Server could not respond to your request'
            ]
        ]);
        $listener->onKernelException($event);
        $this->assertEquals(501, $event->getResponse()->getStatusCode());
        
        
        $listener->setMapping(['InvalidArgumentException' => []]);
        $listener->onKernelException($event);
        $this->assertEquals(500, $event->getResponse()->getStatusCode());
    }
    
    
    /**
     * @covers ::onKernelException
     */
    public function test_onKernelException_HttpExceptionInterface()
    {
        $listener = new ExceptionListener();
        $exception = new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        $event = new GetResponseForExceptionEvent(
            $this->getBBApp()->getController(), 
            new Request(), 
            FrontController::MASTER_REQUEST, 
            $exception
        );
        $listener->onKernelException($event);
        
        $this->assertEquals($exception->getStatusCode(), $event->getResponse()->getStatusCode());
    }
    
}