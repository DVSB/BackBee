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

namespace BackBee\Rest\Tests\EventListener;

use BackBee\Rest\EventListener\ValidationListener;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Request;
use BackBee\Tests\TestCase;
use BackBee\Rest\Tests\Fixtures\Controller\FixtureAnnotatedController;
use BackBee\FrontController\FrontController;

/**
 * Validation Listener class
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\EventListener\ValidationListener
 */
class ValidationListenerTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function test__construct()
    {
        $listener = new ValidationListener($this->getBBApp()->getContainer());

        $this->assertInstanceOf('BackBee\Rest\EventListener\ValidationListener', $listener);
    }

    /**
     * @covers ::onKernelController
     * @covers ::setDefaultValues
     * @covers ::validateParams
     * @covers ::getControllerActionMetadata
     * @covers ::getViolationsParameterName
     * @expectedException BackBee\Rest\Exception\ValidationException
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
     * @covers ::getViolationsParameterName
     */
    public function test_onKernelController_invalidInputWithViolationsActionArgument_RequestParam()
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
     * @covers ::getViolationsParameterName
     */
    public function test_onKernelController_validInput_RequestParam()
    {
        $listener = new ValidationListener($this->getBBApp()->getContainer());
        $data = ['name' => 'NameValid'];
        $request = new Request([], $data);
        $controller = array(new FixtureAnnotatedController(), 'requestParamsWithoutViolationsArgumentAction');

        $event = new FilterControllerEvent(new FrontController(), $controller, $request, FrontController::MASTER_REQUEST);
        $listener->onKernelController($event);
        $this->assertEquals($data['name'], $request->request->get('name'));
        $this->assertEquals('DefaultName', $request->request->get('nameDefault'));
        $this->assertEquals(null, $request->request->get('fieldWithoutRequirements'));
    }

     /**
     * @covers ::onKernelController
     * @covers ::setDefaultValues
     * @covers ::validateParams
     * @covers ::getControllerActionMetadata
     * @covers ::getViolationsParameterName
     */
    public function test_onKernelController_validInput_QueryParam()
    {
        $listener = new ValidationListener($this->getBBApp()->getContainer());
        $data = ['queryParamField' => 'value'];
        $request = new Request($data);
        $controller = array(new FixtureAnnotatedController(), 'queryParamsAction');

        $event = new FilterControllerEvent(new FrontController(), $controller, $request, FrontController::MASTER_REQUEST);
        $listener->onKernelController($event);
        $this->assertEquals($data['queryParamField'], $request->query->get('queryParamField'));
    }

    /**
     * @covers ::onKernelController
     * @covers ::setDefaultValues
     * @covers ::validateParams
     * @covers ::getControllerActionMetadata
     * @covers ::getViolationsParameterName
     */
    public function test_onKernelController_noMetadata()
    {
        $listener = new ValidationListener($this->getBBApp()->getContainer());
        $data = ['param' => 'value'];
        $request = new Request([], $data);
        $controller = array(new FixtureAnnotatedController(), 'noMetadataAction');
        $event = new FilterControllerEvent(new FrontController(), $controller, $request, FrontController::MASTER_REQUEST);
        $listener->onKernelController($event);

        $this->assertEquals($data['param'], $request->request->get('param'));
    }
}
