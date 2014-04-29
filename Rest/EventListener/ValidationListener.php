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
    Symfony\Component\Validator\ConstraintViolationList;
use BackBuilder\Event\Listener\APathEnabledListener;

/**
 * Request validation listener
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class ValidationListener extends APathEnabledListener
{
    
    /**
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Core request handler
     *
     * @param GetResponseEvent $event The event
     * @throws BadRequestHttpException
     * @throws UnsupportedMediaTypeHttpException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        $request = $controller[0]->getRequest();
        $metadata = $this->getControllerActionMetadata($controller);
        
        if(null !== $metadata) {
            $violations = new ConstraintViolationList();

            if(count($metadata->queryParams)) {
                $queryViolations = $this->validateParams($controller[0]->getValidator(), $metadata->queryParams, $request->query);
                $violations->addAll($queryViolations);
                
                // set defaults
                $this->setDefaultValues($metadata->queryParams, $request->query);
            } elseif(count($metadata->requestParams)) {
                $requestViolations = $this->validateParams($controller[0]->getValidator(), $metadata->requestParams, $request->request);
                $violations->addAll($requestViolations);
                
                // set defaults
                $this->setDefaultValues($metadata->requestParams, $request->request);
            }
            if(count($violations) > 0) {
                $request->attributes->set('violations', $violations);
            }
        }
    }
    
    /**
     * Set default values
     * 
     * @param array $params
     * @param \Symfony\Component\HttpFoundation\ParameterBag $values
     */
    protected function setDefaultValues(array $params, ParameterBag $values)
    {
        foreach($params as $param) {
            if(!array_key_exists('default', $param)) {
                continue;
            }

            if(null === $values->get($param['name'])) {
                $values->set($param['name'], $param['default']);
            }
        }
    }
    
    /**
     * Validate params
     * 
     * @param \Symfony\Component\Validator\Validator $validator
     * @param array $params
     * @param \Symfony\Component\HttpFoundation\ParameterBag $values
     * @return \Symfony\Component\Validator\ConstraintViolationList
     */
    protected function validateParams(Validator $validator, array $params, ParameterBag $values)
    {
        $violations = new ConstraintViolationList();
        foreach($params as $param) {
            if(empty($param['requirements'])) {
                continue;
            }
            
            $value = $values->get($param['name']);
            
            $paramViolations = $validator->validateValue($value, $param['requirements']);
            
            $violations->addAll($paramViolations);
        }
        
        return $violations;
    }
    
    /**
     * 
     * @param mixed $controller
     * @return \Metadata\ClassHierarchyMetadata|\Metadata\MergeableClassMetadata
     */
    protected function getControllerActionMetadata($controller)
    {
        $controllerClass = get_class($controller[0]);
        
        $metadata = $this->container->get('rest.metadata.factory')->getMetadataForClass($controllerClass);
        
        $controllerMetadata = $metadata->getOutsideClassMetadata();
        
        if(array_key_exists($controller[1], $controllerMetadata->methodMetadata)) {
            return $controllerMetadata->methodMetadata[$controller[1]];
        }
        
        return null;
    }
}
