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

namespace BackBee\Rest\Tests\Controller;

use BackBee\Rest\Controller\AclController;
use BackBee\Rest\Tests\Fixtures\Model\MockUser;
use BackBee\Security\User;
use BackBee\Site\Site;
use BackBee\Tests\TestCase;

/**
 * Test for AclController class
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\Controller\ARestController
 */
class ARestControllerTest extends TestCase
{
    protected $user;
    protected $site;
    protected $groupEditor;

    protected function setUp()
    {
        $this->initAutoload();
        $bbapp = $this->getBBApp();
        $this->initDb($bbapp);
        $this->getBBApp()->setIsStarted(true);
    }

    protected function getController()
    {
        $controller = new \BackBee\Rest\Tests\Fixtures\Controller\MockARestImplController();
        $controller->setContainer($this->getBBApp()->getContainer());

        return $controller;
    }

    /**
     * @covers ::formatCollection
     * @covers ::getSerializer()
     */
    public function test_formatCollection()
    {
        $arrayCollection = [new MockUser()];

        $serialized = $this->getController()->formatCollection($arrayCollection, 'json');

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
        $json = $this->getController()->formatItem(new MockUser(), 'json');
        $this->assertEquals([
            'id' => 1,
            'login' => 'userLogin',
        ], json_decode($json, true));

        $this->getController()->getRequest()->query->set('jsonp.callback', 'JSONP.callback');
        $jsonp = $this->getController()->formatItem(new MockUser(), 'jsonp');

        $this->assertEquals('/**/JSONP.callback('.json_encode(['id' => 1, 'login' => 'userLogin']).')', $jsonp);
    }

    /**
     * @covers ::formatItem
     * @covers ::getSerializer()
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function test_formatItem_jsonpXssAttack()
    {
        $this->getController()->getRequest()->query->set('jsonp.callback', '(function xss(x){evil()})');
        $this->getController()->formatItem(new MockUser(), 'jsonp');
    }

    /**
     * @covers ::deserializeEntity
     * @covers ::getSerializer()
     */
    public function test_deserializeEntity()
    {
        $user = new User('userLogin', 'userPassword');

        $data = ['login' => 'userLoginChanged'];

        $serialized = $this->getController()->deserializeEntity($data, $user);

        $this->assertEquals($data['login'], $user->getLogin());
    }

    /**
     * @covers ::create404Response
     */
    public function test_create404Response()
    {
        $response = $this->getController()->create404ResponseAction();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @covers ::createValidationException
     */
    public function test_create404createValidationExceptionAction()
    {
        $response = $this->getController()->createValidationExceptionAction();
        $this->assertInstanceOf('BackBee\Rest\Exception\ValidationException', $response);
    }
}
