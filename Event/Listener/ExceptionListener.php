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

namespace BackBee\Event\Listener;

use BackBee\BBApplication;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 */
class ExceptionListener
{
    /**
     * @var BackBee\Renderer
     */
    private $renderer;

    /**
     * @var Symfony\Component\HttpFoundation\Response
     */
    private $response;

    public function __construct(BBApplication $application)
    {
        $this->application = $application;
        $this->renderer = $application->getRenderer();
        $this->response = new Response();
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $statusCode = $exception->getStatusCode();

        if($this->application->isDebugMode()){
            return $this->createDebugResponse($event, $exception);
        }

        switch($statusCode) {
            case 404:
            case 500:
                $parameterKey = "error.$statusCode";
            break;
            default:
                $parameterKey = 'error.default';
        }

        $parameter = $this->application->getContainer()->getParameter($parameterKey);
        $view = $this->getErrorTemplate($parameter);

        $this->response
            ->setStatusCode($statusCode)
            ->setContent($this->renderer->partial($view, ['error' => $exception]));

        $event->setResponse($this->response);
        $filterEvent = new FilterResponseEvent($event->getKernel(), $event->getRequest(), $event->getRequestType(), $event->getResponse());
        $event->getDispatcher()->dispatch(KernelEvents::RESPONSE, $filterEvent);
    }

    /**
     * Create a response from Symfony Debug component
     */
    private function createDebugResponse(GetResponseForExceptionEvent $event, \Exception $exception)
    {
        try {
            if (!$event->hasResponse()) {
                throw $exception;
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return;
    }

    /**
     * Returns the path of the template for the selected http status code
     * or default one.
     *
     * @input string $parameter path related to 404|500|default HTTP status code
     *
     * @return string
     */
    private function getErrorTemplate($parameter)
    {
        return $this->application
            ->getContainer()
            ->getParameter('error.base_folder')
            . $parameter
        ;
    }
}
