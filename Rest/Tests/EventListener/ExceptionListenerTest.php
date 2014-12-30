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

namespace BackBee\Rest\Tests\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

use BackBee\FrontController\FrontController;
use BackBee\Rest\EventListener\ExceptionListener;
use BackBee\Security\Exception\SecurityException;
use BackBee\Tests\TestCase;

/**
 * Test for ExceptionListener class
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\EventListener\ExceptionListener
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
                'message' => 'Server could not respond to your request',
            ],
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

        // ValidationException
        $exception = new \BackBee\Rest\Exception\ValidationException(
            new \Symfony\Component\Validator\ConstraintViolationList([
                new \Symfony\Component\Validator\ConstraintViolation(
                    'Validation Error', 'Validation Error', [], 'root', 'property', 'valueInvalid'
                ),
            ])
        );
        $event = new GetResponseForExceptionEvent(
            $this->getBBApp()->getController(),
            new Request(),
            FrontController::MASTER_REQUEST,
            $exception
        );
        $listener->onKernelException($event);
        $this->assertEquals($exception->getStatusCode(), $event->getResponse()->getStatusCode());
        $response = json_decode($event->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('property', $response['errors']);
    }

    /**
     * @covers ::onKernelException
     */
    public function test_onKernelException_SecurityException()
    {
        $listener = new ExceptionListener();

        $exception = new SecurityException("", SecurityException::EXPIRED_AUTH);
        $event = new GetResponseForExceptionEvent(
            $this->getBBApp()->getController(),
            new Request(),
            FrontController::MASTER_REQUEST,
            $exception
        );
        $listener->onKernelException($event);

        $this->assertEquals(401, $event->getResponse()->getStatusCode());

        $exception = new SecurityException("", SecurityException::EXPIRED_TOKEN);
        $event = new GetResponseForExceptionEvent(
            $this->getBBApp()->getController(),
            new Request(),
            FrontController::MASTER_REQUEST,
            $exception
        );
        $listener->onKernelException($event);

        $this->assertEquals(401, $event->getResponse()->getStatusCode());

        $exception = new SecurityException("", SecurityException::UNKNOWN_USER);
        $event = new GetResponseForExceptionEvent(
            $this->getBBApp()->getController(),
            new Request(),
            FrontController::MASTER_REQUEST,
            $exception
        );
        $listener->onKernelException($event);

        $this->assertEquals(404, $event->getResponse()->getStatusCode());

        $exception = new SecurityException("", SecurityException::INVALID_CREDENTIALS);
        $event = new GetResponseForExceptionEvent(
            $this->getBBApp()->getController(),
            new Request(),
            FrontController::MASTER_REQUEST,
            $exception
        );
        $listener->onKernelException($event);

        $this->assertEquals(401, $event->getResponse()->getStatusCode());
    }
}
