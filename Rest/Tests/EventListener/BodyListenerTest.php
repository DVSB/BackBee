<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use BackBee\FrontController\FrontController;
use BackBee\Rest\Encoder\ContainerEncoderProvider;
use BackBee\Rest\EventListener\BodyListener;
use BackBee\Tests\TestCase;

/**
 * Test for BodyListener class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\EventListener\BodyListener
 * @group Rest
 */
class BodyListenerTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function test__construct()
    {
        $provider = new ContainerEncoderProvider([
            'json' => 'rest.encoder.json', 'xml' => 'rest.encoder.xml',
        ]);
        $provider->setContainer($this->getBBApp()->getContainer());
        $listener = new BodyListener($provider, true);

        $this->assertInstanceOf('BackBee\Rest\EventListener\BodyListener', $listener);
    }

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
     * @expectedException \Symfony\Component\Serializer\Exception\UnexpectedValueException
     *
     * @covers ::onRequest
     */
    public function testOnRequest_wrongContent()
    {
        $request = Request::create('test', "POST", [], [], [], ['CONTENT_TYPE' => 'application/json'], '<xml></xml>');
        $this->invokeOnRequest($request, true);
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
        $this->invokeOnRequest($request, true);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException
     * @expectedExceptionMessage Request body format 'html' not supported
     *
     * @covers ::onRequest
     */
    public function testOnRequest_unsupportedContentType()
    {
        $data = ['param' => 'value'];
        $request = Request::create('test', "POST", [], [], [], ['CONTENT_TYPE' => 'text/html'], json_encode($data));
        $this->invokeOnRequest($request, true);
    }

    /**
     * @covers ::onRequest
     */
    public function test_onRequest_noContentType_noException()
    {
        $data = ['param' => 'value'];
        $request = Request::create('test', "POST", [], [], [], [], json_encode($data));
        $this->invokeOnRequest($request, false);

        $this->assertEquals([], $request->request->all());
    }

    /**
     * @covers ::onRequest
     */
    public function testOnRequest_noContentTypeNoContent()
    {
        $request = Request::create('test', "DELETE");
        $this->invokeOnRequest($request);
    }

    /**
     *
     */
    private function invokeOnRequest(Request $request, $throwExceptionOnUnsupportedContentType = false)
    {
        $provider = new ContainerEncoderProvider([
            'json' => 'rest.encoder.json', 'xml' => 'rest.encoder.xml',
        ]);
        $provider->setContainer($this->getBBApp()->getContainer());
        $listener = new BodyListener($provider, $throwExceptionOnUnsupportedContentType);

        $event = new GetResponseEvent($this->getBBApp()->getController(), $request, FrontController::MASTER_REQUEST);
        $listener->onRequest($event);
    }
}
