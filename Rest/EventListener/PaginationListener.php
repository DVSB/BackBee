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

namespace BackBuilder\Rest\EventListener;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Validator\Validator,
    Symfony\Component\Validator\ConstraintViolationList,
    Symfony\Component\Validator\ConstraintViolation;
use BackBuilder\Event\Listener\APathEnabledListener;


/**
 * Pagination listener
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class PaginationListener extends APathEnabledListener
{
    
    
    /**
     * Controller
     *
     * @param GetResponseEvent $event The event
     * @throws BadRequestHttpException
     * @throws UnsupportedMediaTypeHttpException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        $request = $event->getRequest();
        
        
        if (in_array($request->getMethod(), array('GET', 'HEAD', 'DELETE')))  {
            // pagination only makes sense with GET or DELETE methods
            return;
        }
        
        $metadata = $this->getControllerActionMetadata($controller);
        
        if(null !== $metadata) {
            $violations = new ConstraintViolationList();

            if(count($metadata->queryParams)) {
                $queryViolations = $this->validateParams($this->container->get('validator'), $metadata->queryParams, $request->query);
                $violations->addAll($queryViolations);
                
                // set defaults
                $this->setDefaultValues($metadata->queryParams, $request->query);
            } elseif(count($metadata->requestParams)) {
                $requestViolations = $this->validateParams($this->container->get('validator'), $metadata->requestParams, $request->request);
                $violations->addAll($requestViolations);
                
                // set defaults
                $this->setDefaultValues($metadata->requestParams, $request->request);
            }
            if(count($violations) > 0) {
                $request->attributes->set('violations', $violations);
            }
        }
    }
}
