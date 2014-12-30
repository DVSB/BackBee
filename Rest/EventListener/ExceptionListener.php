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

namespace BackBee\Rest\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

use BackBee\Event\Listener\APathEnabledListener;
use BackBee\FrontController\Exception\FrontControllerException;
use BackBee\Security\Exception\SecurityException;

/**
 * Body listener/encoder
 *
 * @category    BackBee
 * @package     BackBee\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class ExceptionListener extends APathEnabledListener
{
    /**
     * @var array
     */
    private $mapping;

    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$this->isEnabled($event->getRequest())) {
            return;
        }

        $exception = $event->getException();
        $exceptionClass = get_class($exception);

        if (isset($this->mapping[$exceptionClass])) {
            $code = isset($this->mapping[$exceptionClass]['code']) ? $this->mapping[$exceptionClass]['code'] : 500;
            $message = isset($this->mapping[$exceptionClass]['message']) ? $this->mapping[$exceptionClass]['message'] : Response::$statusTexts[$code];

            if (!$event->getResponse()) {
                $event->setResponse(new Response());
            }

            $event->getResponse()->setStatusCode($code, $message);
            $event->getResponse()->headers->add(array('Content-Type' => 'application/json'));
        } elseif ($exception instanceof HttpExceptionInterface) {
            if (!$event->getResponse()) {
                $event->setResponse(new Response());
            }
            // keep the HTTP status code and headers
            $event->getResponse()->setStatusCode($exception->getStatusCode(), $exception->getMessage());
            $event->getResponse()->headers->add(array('Content-Type' => 'application/json'));

            if ($exception instanceof \BackBee\Rest\Exception\ValidationException) {
                $event->getResponse()->setContent(json_encode(array('errors' => $exception->getErrorsArray())));
            }
        } elseif ($exception instanceof FrontControllerException) {
            if (!$event->getResponse()) {
                $event->setResponse(new Response());
            }
            // keep the HTTP status code
            $event->getResponse()->setStatusCode($exception->getStatusCode());
        } elseif ($exception instanceof SecurityException) {
            $event->setResponse(new Response());

            $statusCode = 403;

            switch ($exception->getCode()) {
                case SecurityException::UNKNOWN_USER:
                    $statusCode = 404;
                    break;

                case SecurityException::INVALID_CREDENTIALS:
                case SecurityException::EXPIRED_AUTH:
                case SecurityException::EXPIRED_TOKEN:
                    $statusCode = 401;
                    break;

                default:
                    $statusCode = 403;
            }

            $event->getResponse()->setStatusCode($statusCode, $exception->getMessage());
        }
    }
}
