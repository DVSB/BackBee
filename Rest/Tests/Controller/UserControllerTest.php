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

namespace BackBuilder\Rest\Tests\Controller;

use BackBuilder\Rest\EventListener\PaginationListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Symfony\Component\HttpFoundation\Request;

use BackBuilder\FrontController\FrontController;
use BackBuilder\Rest\Controller\UserController;
use BackBuilder\Test\TestCase;


use BackBuilder\Security\User,
    BackBuilder\Security\Group;

/**
 * Test for UserController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class UserControllerTest extends TestCase
{

    protected $bbapp;
    
    protected function setUp()
    {
        $this->initAutoload();
        $this->bbapp = new \BackBuilder\BBApplication(null, 'test');
        $this->initDb($this->bbapp);
        $this->bbapp->start();
        
        // save user
        $group = new Group();
        $group->setName('groupName');
        $group->setIdentifier('GROUP_ID');
        $this->bbapp->getEntityManager()->persist($group);
        
        // valid user
        $user = new User();
        $user->addGroup($group);
        $user->setLogin('user123');
        $user->setPassword('password123');
        $user->setActivated(true);
        $this->bbapp->getEntityManager()->persist($user);
        
        // inactive user
        $user = new User();
        $user->addGroup($group);
        $user->setLogin('user123inactive');
        $user->setPassword('password123');
        $user->setActivated(false);
        $this->bbapp->getEntityManager()->persist($user);
        
        $this->bbapp->getEntityManager()->flush();
    }
    
    protected function getController()
    {
        $controller = new UserController();
        $controller->setContainer($this->bbapp->getContainer());
        
        return $controller;
    }


    public function testLoginAction_TokenCreated()
    {
        $this->assertNull($this->bbapp->getSecurityContext()->getToken());
        
        $request = new Request(array(), array(
            'username' => 'user123',
            'password' => 'password123',
            '_action' => 'loginAction',
            '_controller' => 'BackBuilder\Rest\Controller\UserController',
        ));
        $controller = $this->getController();
        
        $response = $controller->loginAction($request);
        
        $this->assertInstanceOf('BackBuilder\Security\Token\UsernamePasswordToken', $this->bbapp->getSecurityContext()->getToken());
    }
    
    public function testLoginAction_InvalidUser()
    {
        $this->assertNull($this->bbapp->getSecurityContext()->getToken());
        
        $request = new Request(array(), array(
            'username' => 'userThatDoesntExist',
            'password' => 'password123',
            '_action' => 'loginAction',
            '_controller' => 'BackBuilder\Rest\Controller\UserController',
        ));
        $controller = $this->getController();
        
        $response = $controller->loginAction($request);
        
        // TODO in case of invalid user a 404 error should be returned
        $this->assertEquals(401, $response->getStatusCode());
        
        $this->assertNull($this->bbapp->getSecurityContext()->getToken());
    }
    
    public function testLoginAction_InvalidPassword()
    {
        $this->assertNull($this->bbapp->getSecurityContext()->getToken());
        
        $request = new Request(array(), array(
            'username' => 'user123',
            'password' => 'passwordInvalid',
            '_action' => 'loginAction',
            '_controller' => 'BackBuilder\Rest\Controller\UserController',
        ));
        $controller = $this->getController();
        
        $response = $controller->loginAction($request);
        
        $this->assertEquals(401, $response->getStatusCode());
        
        $this->assertNull($this->bbapp->getSecurityContext()->getToken());
    }
    
    public function testLoginAction_InactiveUser()
    {
        $this->assertNull($this->bbapp->getSecurityContext()->getToken());
        
        $request = new Request(array(), array(
            'username' => 'user123inactive',
            'password' => 'password123',
            '_action' => 'loginAction',
            '_controller' => 'BackBuilder\Rest\Controller\UserController',
        ));
        $controller = $this->getController();
        
        $response = $controller->loginAction($request);
        
        $this->assertEquals(403, $response->getStatusCode());
        
        $this->assertNull($this->bbapp->getSecurityContext()->getToken());
    }
    
    public function testLoginAction_NoData()
    {
        $this->assertNull($this->bbapp->getSecurityContext()->getToken());
        
        $request = new Request(array(), array(
            'username' => 'user123',
            'password' => 'password123',
            '_action' => 'loginAction',
            '_controller' => 'BackBuilder\Rest\Controller\UserController',
        ));
        
        $controller = $this->getController();
        
        $response = $controller->loginAction($request);
        $this->assertEquals(204, $response->getStatusCode());
    }
    
    public function testLoginAction_ReturnData()
    {
        $request = new Request(array(), array(
            'username' => 'user123',
            'password' => 'password123',
            'includeUserData' => 1,
            'includePermissionsData' => 1,
            '_action' => 'loginAction',
            '_controller' => 'BackBuilder\Rest\Controller\UserController',
        ));
        
        $controller = $this->getController();
        
        $response = $controller->loginAction($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('permissions', $res);
        $this->assertContains('GROUP_ID', $res['permissions']);
        $this->assertArrayHasKey('user', $res);
    }
    
    
    /**
     * @depends testLoginAction_TokenCreated
     */
    public function testLogoutAction()
    {
        $this->assertNull($this->bbapp->getSecurityContext()->getToken());
        // login first
        $request = new Request(array(), array(
            'username' => 'user123',
            'password' => 'password123',
            '_action' => 'loginAction',
            '_controller' => 'BackBuilder\Rest\Controller\UserController',
        ));
        $controller = $this->getController();
        
        $response = $controller->loginAction($request);
        
        $this->assertInstanceOf('BackBuilder\Security\Token\UsernamePasswordToken', $this->bbapp->getSecurityContext()->getToken());
        
        // logout
        $request = new Request(array(), array(
            '_action' => 'logoutAction',
            '_controller' => 'BackBuilder\Rest\Controller\UserController',
        ));
        
        $response = $controller->logoutAction($request);
        
        $this->assertEquals(204, $response->getStatusCode());
        
        $this->assertNull($this->bbapp->getSecurityContext()->getToken());
    }

    protected function tearDown()
    {
        $this->dropDb($this->bbapp);
        $this->bbapp->stop();
    }
}