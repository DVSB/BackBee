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

namespace BackBuilder\Rest\Controller;

use Symfony\Component\HttpFoundation\Response;

use BackBuilder\Controller\Controller,
    BackBuilder\Rest\Formatter\IFormatter,
    BackBuilder\Serializer\SerializerBuilder;

use JMS\Serializer\Serializer,
    JMS\Serializer\DeserializationContext,
    JMS\Serializer\SerializationContext;

use BackBuilder\Rest\Exception\ValidationException;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Symfony\Component\Validator\ConstraintViolationList,
    Symfony\Component\Validator\ConstraintViolation;

/**
 * Abstract class for an api controller
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
abstract class ARestController extends Controller implements IRestController, IFormatter
{
    /**
     *
     * @var \JMS\Serializer\Serializer
     */
    protected $serializer;
    
    /**
     * 
     *
     * @access public
     */
    public function optionsAction($endpoint) 
    {
        // TODO
        
        return array();
    }
    
    
    /*
     * Default formatter for a collection of objects
     * 
     * Implements BackBuilder\Rest\Formatter\IFormatter::formatCollection($collection)
     */
    public function formatCollection($collection, $format = 'json') 
    {
        $items = array();
        
        foreach($collection as $item) {
            $items[] = $item;
        }
        
        return $this->getSerializer()->serialize($items, 'json');
    }
    
    /**
     * Serializes an object
     * 
     * Implements BackBuilder\Rest\Formatter\IFormatter::formatItem($item)
     * @param mixed $item
     * @return array
     */
    public function formatItem($item, $format = 'json')
    {
        $formatted = null;
        
        switch ($format) {
            case 'json':
                // serialize properties with null values
                $context = new SerializationContext();
                $context->setSerializeNull(true);
                $formatted = $this->getSerializer()->serialize($item, 'json', $context);
                break;
            case 'jsonp':
                $callback = $this->getRequest()->query->get('jsonp.callback', 'JSONP.callback');
                
                // validate against XSS
                $validator = new \JsonpCallbackValidator();
                if (!$validator->validate($callback)) {
                    throw new BadRequestHttpException('Invalid JSONP callback value');
                }
                
                
                $context = new SerializationContext();
                $context->setSerializeNull(true);
                $json = $this->getSerializer()->serialize($item, 'json', $context);
                
                $formatted = sprintf('/**/%s(%s)', $callback, $json);
                break;
            default:
                // any other format is not supported
                throw new \InvalidArgumentException(sprintf('Format not supported: %s', $format));
        }

        return $formatted;
    }
    
    /**
     * Deserialize data into Doctrine entity
     * 
     * @param string|mixed $item Either a valid Entity class name, or a Doctrine Entity object
     * @return mixed
     */
    public function deserializeEntity(array $data, $entityOrClass)
    {
        $context = null;
        if(is_object($entityOrClass)) {
            $context = DeserializationContext::create();
            $context->attributes->set('target', $entityOrClass);
            $entityOrClass = get_class($entityOrClass);
        }
 
        return $this->getSerializer()->deserialize(json_encode($data), $entityOrClass, 'json',  $context);
    }
    
    
    /**
     * Create a RESTful response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function createResponse($content = '', $statusCode = 200, $contentType = 'application/json')
    {
        $response = new Response($content, $statusCode);
        $response->headers->set('Content-Type', $contentType);
        
        return $response;
    }

    /**
     * 
     * @param type $message
     * @return type
     */
    protected function create404Response($message = null)
    {
        $response = $this->createResponse();
        $response->setStatusCode(404, $message);
        
        return $response;
    }
    
    /**
     * @return \JMS\Serializer\Serializer
     */
    protected function getSerializer()
    {
        if(null === $this->serializer) {
            $builder = SerializerBuilder::create()
                ->setObjectConstructor($this->getContainer()->get('serializer.object_constructor'))
                ->setPropertyNamingStrategy($this->getContainer()->get('serializer.naming_strategy'))
                ->setAnnotationReader($this->getContainer()->get('annotation_reader'))
                ->setMetadataDriver($this->getContainer()->get('serializer.metadata_driver'))
            ;
            
            $this->serializer = $builder->build();
        }
        
        return $this->serializer;
    }
    
    protected function createValidationException($field, $value, $message)
    {
        return new ValidationException(new ConstraintViolationList(array(
            new ConstraintViolation($message, $message, array(), $field, $field, $value)
        )));
    }
}