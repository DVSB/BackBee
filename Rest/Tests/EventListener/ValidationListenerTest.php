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

use BackBuilder\Rest\EventListener\ValidationListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Symfony\Component\HttpFoundation\Request;

use BackBuilder\Tests\TestCase;

use BackBuilder\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController;
use BackBuilder\FrontController\FrontController;



/**
 * Validation Listener class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 * 
 * @coversDefaultClass \BackBuilder\Rest\EventListener\ValidationListener
 */
class ValidationListenerTest extends TestCase
{

    /**
     * @covers ::__construct
     */
    public function test__construct()
    {
        $listener = new ValidationListener($this->getBBApp()->getContainer());
        
        $this->assertInstanceOf('BackBuilder\Rest\EventListener\ValidationListener', $listener);
    }
    
    
    /**
     * @covers ::onKernelController
     * @covers ::setDefaultValues
     * @covers ::validateParams
     * @covers ::getControllerActionMetadata
     * @expectedException BackBuilder\Rest\Exception\ValidationException
     */
    public function test_onKernelController_invalidInputWithoutViolationsActionArgument()
    {
        $listener = new ValidationListener($this->getBBApp()->getContainer());
        $data = ['name' => 'NameThatIsVeryLong_Exceeds50CharactersLimitDeginedInTheController_blablablablabla'];
        $request = new Request([], $data);
        $controller = array(new FixtureAnnotatedController(), 'requestParamsWithoutViolationsArgumentAction');
        
        $event = new FilterControllerEvent(new FrontController(), $controller, $request, FrontController::MASTER_REQUEST);
        $listener->onKernelController($event);
    }
    
    
    /**
     * @covers ::onKernelController
     * @covers ::setDefaultValues
     * @covers ::validateParams
     * @covers ::getControllerActionMetadata
     */
    public function test_onKernelController_invalidInputWithViolationsActionArgument()
    {
        $listener = new ValidationListener($this->getBBApp()->getContainer());
        $data = ['name' => 'NameThatIsVeryLong_Exceeds50CharactersLimitDeginedInTheController_blablablablabla'];
        $request = new Request([], $data);
        $controller = array(new FixtureAnnotatedController(), 'requestParamsWithViolationsArgumentAction');
        
        $event = new FilterControllerEvent(new FrontController(), $controller, $request, FrontController::MASTER_REQUEST);
        $listener->onKernelController($event);
        
        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $request->attributes->get('violations'));
    }
    
    /**
     * @covers ::onKernelController
     * @covers ::setDefaultValues
     * @covers ::validateParams
     * @covers ::getControllerActionMetadata
     */
    public function test_onKernelController_validInput()
    {
        $listener = new ValidationListener($this->getBBApp()->getContainer());
        $data = ['name' => 'NameValid'];
        $request = new Request([], $data);
        $controller = array(new FixtureAnnotatedController(), 'requestParamsWithoutViolationsArgumentAction');
        
        $event = new FilterControllerEvent(new FrontController(), $controller, $request, FrontController::MASTER_REQUEST);
        $listener->onKernelController($event);
        $this->assertEquals($data['name'], $request->request->get('name'));
    }
    

}