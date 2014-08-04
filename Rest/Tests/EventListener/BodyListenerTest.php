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

use BackBuilder\Tests\TestCase;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\Serializer\Encoder\JsonEncoder;

use BackBuilder\Rest\EventListener\BodyListener,
    BackBuilder\FrontController\FrontController,
    BackBuilder\Rest\Encoder\ContainerEncoderProvider;


/**
 * Test for AuthController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 * 
 * @coversDefaultClass \BackBuilder\Rest\EventListener\BodyListener
 */
class BodyListenerTest extends TestCase
{

    /**
     * @covers ::onRequest
     */
    public function testOnRequest()
    {
        $data = ['param' => 'value'];
        $request = Request::create('test', "POST", [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $this->invokeOnRequest($request);
        $this->assertEquals($data, $request->request->all());
    }
    
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * 
     * @covers ::onRequest
     */
    public function testOnRequest_wrongContent()
    {
        $request = Request::create('test', "POST", [], [], [], ['CONTENT_TYPE' => 'application/json'], '<xml></xml>');
        $this->invokeOnRequest($request);
    }
    
    /**
     * @covers ::onRequest
     */
    public function testOnRequest_noContent()
    {
        $request = Request::create('test', "POST", [], [], [], ['CONTENT_TYPE' => 'application/json']);
        $this->invokeOnRequest($request);
        $this->assertEquals([], $request->request->all());
    }
    
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException
     * @expectedExceptionMessage Format of the request content was not recognized
     * 
     * @covers ::onRequest
     */
    public function testOnRequest_noContentType()
    {
        $data = ['param' => 'value'];
        $request = Request::create('test', "POST", [], [], [], [], json_encode($data));
        $this->invokeOnRequest($request);
    }
    
    /**
     * 
     * @covers ::onRequest
     */
    public function testOnRequest_noContentTypeNoContent()
    {
        $request = Request::create('test', "DELETE");
        $this->invokeOnRequest($request);
    }
    
    /**
     * 
     * @return \BackBuilder\Rest\EventListener\BodyListener
     */
    private function invokeOnRequest(Request $request)
    {
        $provider = new ContainerEncoderProvider([
            'json' => 'rest.encoder.json', 'xml' => 'rest.encoder.xml'
        ]);
        $provider->setContainer($this->getBBApp()->getContainer());
        $listener = new BodyListener($provider, true);
        
        $event = new GetResponseEvent($this->getBBApp()->getController(), $request, FrontController::MASTER_REQUEST);
        $listener->onRequest($event);
    }
    
}