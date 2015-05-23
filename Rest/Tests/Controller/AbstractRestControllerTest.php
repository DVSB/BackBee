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

namespace BackBee\Rest\Tests\Controller;

use BackBee\Rest\Controller\AclController;
use BackBee\Rest\Tests\Fixtures\Model\MockUser;
use BackBee\Security\User;
use BackBee\Site\Site;
use BackBee\Rest\Tests\Fixtures\Controller\MockAbstractRestImplController;
use BackBee\Tests\BackBeeTestCase;

/**
 * Test for AclController class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\Controller\AbstractRestController
 * @group Rest
 */
class AbstractRestControllerTest extends BackBeeTestCase
{
    protected $controller;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->controller = new MockAbstractRestImplController();
        $this->controller->setContainer(self::$app->getContainer());
    }

    /**
     * @covers ::formatCollection
     * @covers ::getSerializer()
     */
    public function test_formatCollection()
    {
        $arrayCollection = [new MockUser()];

        $serialized = $this->controller->formatCollection($arrayCollection, 'json');

        $this->assertEquals([[
            'id' => 1,
            'login' => 'userLogin',
        ]], json_decode($serialized, true));
    }

    /**
     * @covers ::formatItem
     * @covers ::getSerializer()
     */
    public function test_formatItem()
    {
        $json = $this->controller->formatItem(new MockUser(), 'json');
        $this->assertEquals([
            'id' => 1,
            'login' => 'userLogin',
        ], json_decode($json, true));

        $this->controller->getRequest()->query->set('jsonp.callback', 'JSONP.callback');
        $jsonp = $this->controller->formatItem(new MockUser(), 'jsonp');

        $this->assertEquals('/**/JSONP.callback('.json_encode(['id' => 1, 'login' => 'userLogin']).')', $jsonp);
    }

    /**
     * @covers ::formatItem
     * @covers ::getSerializer()
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function test_formatItem_jsonpXssAttack()
    {
        $this->controller->getRequest()->query->set('jsonp.callback', '(function xss(x){evil()})');
        $this->controller->formatItem(new MockUser(), 'jsonp');
    }

    /**
     * @covers ::deserializeEntity
     * @covers ::getSerializer()
     */
    public function test_deserializeEntity()
    {
        $user = new User('userLogin', 'userPassword');

        $data = ['login' => 'userLoginChanged'];

        $serialized = $this->controller->deserializeEntity($data, $user);

        $this->assertEquals($data['login'], $user->getLogin());
    }

    /**
     * @covers ::create404Response
     */
    public function test_create404Response()
    {
        $response = $this->controller->create404ResponseAction();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @covers ::createValidationException
     */
    public function test_create404createValidationExceptionAction()
    {
        $response = $this->controller->createValidationExceptionAction();
        $this->assertInstanceOf('BackBee\Rest\Exception\ValidationException', $response);
    }
}
