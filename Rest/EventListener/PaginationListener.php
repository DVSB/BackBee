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

use BackBuilder\Rest\Exception\ValidationException;
use Metadata\MetadataFactory;


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
     * @var MetadataFactory
     */
    private $metadataFactory;
    
    private $validator;
    
    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(MetadataFactory $metadataFactory, Validator $validator)
    {
        $this->metadataFactory = $metadataFactory;
        $this->validator = $validator;
    }
    
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
        

        if (!in_array($request->getMethod(), array('GET', 'HEAD', 'DELETE')))  {
            // pagination only makes sense with GET or DELETE methods
            return;
        }
        
        $metadata = $this->getControllerActionMetadata($controller);
        
        if (null === $metadata || null === $metadata->paginationStartName)  {
            // no annotations defined for this controller
            return;
        }

        $start = $request->query->get($metadata->paginationStartName, 0);
        $limit = $request->query->get($metadata->paginationLimitName, $metadata->paginationLimitDefault);

        $violations = new ConstraintViolationList();
        
        $startViolations = $this->validator->validateValue($start, array(
            // NB: Type assert must come first as otherwise it won't be called
            new \Symfony\Component\Validator\Constraints\Type(array(
                'type' => 'numeric',
                'message' =>  sprintf('% must be a positive integer', $metadata->paginationStartName),
            )),
            new \Symfony\Component\Validator\Constraints\Range(array(
                'min' => 0,
                'minMessage' => sprintf('% must be a positive integer', $metadata->paginationStartName),
            ))
            
        ));
        
        $limitViolations = $this->validator->validateValue($limit, array(
            // NB: Type assert must come first as otherwise it won't be called
            new \Symfony\Component\Validator\Constraints\Type(array(
                'type' => 'numeric',
                'message' =>  sprintf('% must be a positive integer', $metadata->paginationLimitName),
            )),
            new \Symfony\Component\Validator\Constraints\Range(array(
                'min' => $metadata->paginationLimitMin,
                'minMessage' => sprintf('% must be greater than or equal to %d', $metadata->paginationLimitName, $metadata->paginationLimitMin),
                'max' => $metadata->paginationLimitMax,
                'maxMessage' => sprintf('% must be less than or equal to %d', $metadata->paginationLimitName, $metadata->paginationLimitMax),
            )),
        ));
        
        $violations->addAll($startViolations);
        $violations->addAll($limitViolations);

        if(count($violations) > 0) {
            throw new ValidationException($violations);
        } 

        // add pagination properties to attributes
        $request->attributes->set($metadata->paginationStartName, $start);
        $request->attributes->set($metadata->paginationLimitName, $limit);

        // remove pagination properties from query
        $request->query->remove($metadata->paginationStartName);
        $request->query->remove($metadata->paginationLimitName);
    }
    
    /**
     * 
     * @param mixed $controller
     * @return \BackBuilder\Rest\Mapping\ActionMetadata
     */
    protected function getControllerActionMetadata($controller)
    {
        $controllerClass = get_class($controller[0]);
        
        $metadata = $this->metadataFactory->getMetadataForClass($controllerClass);
        
        $controllerMetadata = $metadata->getOutsideClassMetadata();
        
        if(array_key_exists($controller[1], $controllerMetadata->methodMetadata)) {
            return $controllerMetadata->methodMetadata[$controller[1]];
        }
        
        return null;
    }
}
