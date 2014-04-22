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

use BackBuilder\Controller\Controller;

/**
 * Abstract class for an api controller
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
abstract class ARestController extends Controller implements IRestController
{
    
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
    public function formatCollection($collection) 
    {
        $data = array();
        foreach($collection as $object) {
            $data[] = $this->formatItem($object);
        }
        
        return $data;
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
        $response->setContent(json_encode(array('Error' => $message)));
        
        return $response;
    }
    
    /**
     * 
     * @param type $message
     * @return type
     */
//    protected function create500Response($message = null)
//    {
//        $response = $this->createResponse();
//        
//        $response->setStatusCode(500, $message);
//        
//        return $response;
//    }
}