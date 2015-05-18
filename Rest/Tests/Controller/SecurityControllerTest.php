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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BackBee\Rest\Controller\SecurityController;
use BackBee\Security\Token\BBUserToken;
use BackBee\Security\User;
use BackBee\Rest\Test\RestTestCase;

/**
 * Test for SecurityController class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\Controller\SecurityController
 * @group Rest
 */
class SecurityControllerTest extends RestTestCase
{
    protected $bbapp;

    protected function setUp()
    {
        $this->initAutoload();

        $this->bbapp = $this->getBBApp();
        $this->initDb($this->bbapp);

        $this->bbapp->start();

        // valid user
        $user = new User();
        $user->setLogin('user123');
        $user->setEmail('user123@provider.com');
        $user->setPassword(md5('password123'));
        $user->setActivated(true);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $this->controller = $this->getController();
    }

    /**
     * @return \BackBee\Security\Tests\Controller\SecurityController
     */
    protected function getController()
    {
        $controller = new SecurityController();
        $controller->setContainer($this->bbapp->getContainer());

        return $controller;
    }

    /**
     * @covers ::firewallAuthenticateAction
     * @covers ::getSecurityContextConfig
     */
    public function testFirewallAuthenticateActionBbArea()
    {
        $created = date('r'); //'Wed, 09 Jul 2014 14:04:27 GMT';
        $nonce = '05a90bfd413c223a3451d68968f9e5fa';
        $username = 'user123';
        $password = 'password123';
        $digest = md5($nonce.$created.md5($password));

        $request = new Request([], array(
            'username' => $username,
            'created' => $created,
            'digest' => $digest,
            'nonce' => $nonce,
        ));

        $response = $this->controller->firewallAuthenticateAction('bb_area', $request);

        $this->assertTrue($response->isOk(), sprintf('HTTP 200 expected, HTTP %s returned.', $response->getStatusCode()));

        $content = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $content);
        $this->assertArrayHasKey('nonce', $content);
        $this->assertEquals($nonce, $content['nonce']);
    }

    /**
     * @expectedException \BackBee\Security\Exception\SecurityException
     * @expectedExceptionMessage Request expired
     *
     * @covers ::firewallAuthenticateAction
     * @covers ::getSecurityContextConfig
     */
    public function testFirewallAuthenticateActionBbAreaExpired()
    {
        $created = date('r', time() - 301); // -5.01 minutes
        $nonce = '05a90bfd413c223a3451d68968f9e5fa';
        $username = 'user123';
        $password = 'password123';
        $digest = md5($nonce.$created.md5($password));

        $request = new Request([], array(
            'username' => $username,
            'created' => $created,
            'digest' => $digest,
            'nonce' => $nonce,
        ));

        $this->controller->firewallAuthenticateAction('bb_area', $request);
    }

    /**
     * @expectedException \BackBee\Security\Exception\SecurityException
     * @expectedExceptionMessage Unknown user
     *
     * @covers ::firewallAuthenticateAction
     * @covers ::getSecurityContextConfig
     */
    public function testFirewallAuthenticateActionBbAreaUserDoesntExist()
    {
        $created = date('r'); //'Wed, 09 Jul 2014 14:04:27 GMT';
        $nonce = '05a90bfd413c223a3451d68968f9e5fa';
        $username = 'userThatDoesntExist';
        $password = 'password1234';
        $digest = md5($nonce.$created.md5($password));

        $request = new Request([], array(
            'username' => $username,
            'created' => $created,
            'digest' => $digest,
            'nonce' => $nonce,
        ));
        $this->controller->firewallAuthenticateAction('bb_area', $request);
    }

    /**
     * @expectedException \BackBee\Security\Exception\SecurityException
     * @expectedExceptionMessage Invalid authentication informations
     *
     * @covers ::firewallAuthenticateAction
     * @covers ::getSecurityContextConfig
     */
    public function testFirewallAuthenticateActionBbAreaInvalidPassword()
    {
        $created = date('r'); //'Wed, 09 Jul 2014 14:04:27 GMT';
        $nonce = '05a90bfd413c223a3451d68968f9e5fa';
        $username = 'user123';
        $password = 'passwordInvalid';
        $digest = md5($nonce.$created.md5($password));

        $request = new Request([], array(
            'username' => $username,
            'created' => $created,
            'digest' => $digest,
            'nonce' => $nonce,
        ));

        $this->controller->firewallAuthenticateAction('bb_area', $request);
    }

    /**
     * @covers ::firewallAuthenticateAction
     * @covers ::getSecurityContextConfig
     */
    public function testFirewallAuthenticateActionInvalidFirewall()
    {
        $response = $this->controller->firewallAuthenticateAction('invalidFirewallName', new Request());
        $this->assertTrue($response->isClientError(), sprintf('HTTP 400 expected, HTTP %s returned.', $response->getStatusCode()));
    }

    /**
     * @covers ::firewallAuthenticateAction
     * @covers ::getSecurityContextConfig
     */
    public function testFirewallAuthenticateActionFirewallWithoutSupportedContexts()
    {
        $response = $this->controller->firewallAuthenticateAction('rest_api_area_test', new Request());
        $this->assertTrue($response->isClientError(), sprintf('HTTP 400 expected, HTTP %s returned.', $response->getStatusCode()));
    }

    /**
     * @covers ::deleteSessionAction
     * note: this won't test user is realy fully authenticated
     * but it's ok because only the authenticated user is able to access this session from outside.
     */
    public function testDeleteSessionAction()
    {
        // authenticated anonymously
        $token = new \BackBee\Security\Token\AnonymousToken('test', 'test');
        $this->getSecurityContext()->setToken($token);
        $request = new Request();
        $request->setSession($this->getBBApp()->getSession());
        $response = $this->controller->deleteSessionAction($request);

        $this->assertTrue($response->isEmpty(), sprintf('HTTP 204 expected, HTTP %s returned.', $response->getStatusCode()));

        // create token
        $token = new BBUserToken();
        $this->getSecurityContext()->setToken($token);
        $request = new Request();
        $request->setSession($this->getBBApp()->getSession());
        $response = $this->controller->deleteSessionAction($request);

        $this->assertTrue($response->isEmpty(), sprintf('HTTP 204 expected, HTTP %s returned.', $response->getStatusCode()));
    }

    /**
     * @covers ::deleteSessionAction
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * note: this won't test user is realy fully authenticated
     * but it's ok because only the authenticated user is able to access this session from outside.
     */
    public function testDeleteSessionActionSessionDoesntExist()
    {
        // session doesnt exist
        $response = $this->controller->deleteSessionAction(new Request());
        $this->assertTrue($response->isClientError(), sprintf('HTTP 401 expected, HTTP %s returned.', $response->getStatusCode()));
    }

    protected function tearDown()
    {
        $this->dropDb($this->getBBApp());
        $this->getBBApp()->stop();
    }
}
