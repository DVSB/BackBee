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


use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use BackBuilder\Rest\Controller\SecurityController;
use BackBuilder\Tests\TestCase;


use BackBuilder\Security\User,
    BackBuilder\Security\Token\BBUserToken;


/**
 * Test for SecurityController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 * 
 * @coversDefaultClass \BackBuilder\Rest\Controller\SecurityController
 */
class SecurityControllerTest extends TestCase
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
        $user->setPassword(md5('password123'));
        $user->setActivated(true);
        
        $this->getEntityManager()->persist($user);
        
        $this->getEntityManager()->flush();
    }
    
    /**
     * 
     * @return \BackBuilder\Security\Tests\Controller\SecurityController
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
     * 
     */
    public function test_firewallAuthenticateAction_bb_area()
    {
        $controller = $this->getController();
        
        $created = date('r'); //'Wed, 09 Jul 2014 14:04:27 GMT';
        $nonce = '05a90bfd413c223a3451d68968f9e5fa';
        $username = 'user123';
        $password = 'password123';
        $digest = md5($nonce . $created . md5($password));
        
        $request = new Request([], array(
            'username' => $username,
            'created' => $created,
            'digest' => $digest,
            'nonce' => $nonce
        ));
        
        $response = $controller->firewallAuthenticateAction('bb_area', $request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $content);
        $this->assertArrayHasKey('nonce', $content);
    }
    
    /**
     * @expectedException \BackBuilder\Security\Exception\SecurityException
     * @expectedExceptionMessage Request expired
     * 
     * @covers ::firewallAuthenticateAction
     * @covers ::getSecurityContextConfig
     */
    public function test_firewallAuthenticateAction_bb_area_expired()
    {
        $controller = $this->getController();
        
        $created = date('r', time() - 301); // -5.01 minutes
        $nonce = '05a90bfd413c223a3451d68968f9e5fa';
        $username = 'user123';
        $password = 'password123';
        $digest = md5($nonce . $created . md5($password));
        
        $request = new Request([], array(
            'username' => $username,
            'created' => $created,
            'digest' => $digest,
            'nonce' => $nonce
        ));
        
        $controller->firewallAuthenticateAction('bb_area', $request);
    }
    
    /**
     * @expectedException \BackBuilder\Security\Exception\SecurityException
     * @expectedExceptionMessage Unknown user
     * 
     * @covers ::firewallAuthenticateAction
     * @covers ::getSecurityContextConfig
     */
    public function test_firewallAuthenticateAction_bb_area_userDoesntExist()
    {
        $controller = $this->getController();
        
        $request = new Request([], array(
            'username' => 'userThatDoesntExist',
            'created' => 'test',
            'digest' => 'test',
            'nonce' => 'test'
        ));
        $controller->firewallAuthenticateAction('bb_area', $request);
    }
    
    /**
     * @expectedException \BackBuilder\Security\Exception\SecurityException
     * @expectedExceptionMessage Invalid authentication informations
     * 
     * @covers ::firewallAuthenticateAction
     * @covers ::getSecurityContextConfig
     */
    public function test_firewallAuthenticateAction_bb_area_invalidPassword()
    {
        $controller = $this->getController();
        
        $created = date('r'); //'Wed, 09 Jul 2014 14:04:27 GMT';
        $nonce = '05a90bfd413c223a3451d68968f9e5fa';
        $username = 'user123';
        $password = 'passwordInvalid';
        $digest = md5($nonce . $created . md5($password));
        
        $request = new Request([], array(
            'username' => $username,
            'created' => $created,
            'digest' => $digest,
            'nonce' => $nonce
        ));
        
        $controller->firewallAuthenticateAction('bb_area', $request);
    }
    
    /**
     * @covers ::firewallAuthenticateAction
     * @covers ::getSecurityContextConfig
     */
    public function test_firewallAuthenticateAction_invalidFirewall()
    {
        $controller = $this->getController();
        
        $response = $controller->firewallAuthenticateAction('invalidFirewallName', new Request());
        
        $this->assertEquals(400, $response->getStatusCode());
    }
    
    /**
     * @covers ::firewallAuthenticateAction
     * @covers ::getSecurityContextConfig
     */
    public function test_firewallAuthenticateAction_firewallWithoutSupportedContexts()
    {
        $controller = $this->getController();
        
        $response = $controller->firewallAuthenticateAction('rest_api_area_test', new Request());
        
        $this->assertEquals(400, $response->getStatusCode());
    }
    
    
    /**
     * @covers ::deleteSessionAction
     */
    public function testDeleteSessionAction()
    {
        $this->getBBApp()->start();
        $controller = $this->getController();
        
        // authenticated anonymously 
        $token = new \BackBuilder\Security\Token\AnonymousToken('test', 'test');
        $this->getSecurityContext()->setToken($token);
        $request = new Request();
        $request->setSession($this->getBBApp()->getSession());
        $response = $controller->deleteSessionAction($request);
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(204, $response->getStatusCode());
        
        // create token
        $token = new BBUserToken();
        $this->getSecurityContext()->setToken($token);
        $request = new Request();
        $request->setSession($this->getBBApp()->getSession());
        $response = $controller->deleteSessionAction($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals(204, $response->getStatusCode());
    }
    
    /**
     * @covers ::deleteSessionAction
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testDeleteSessionAction_sessionDoesntExist()
    {
        $this->getBBApp()->start();
        $controller = $this->getController();
        
        // session doesnt exist
        $response = $controller->deleteSessionAction(new Request());
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(401, $response->getStatusCode());
    }
    
    

    protected function tearDown()
    {
        $this->dropDb($this->getBBApp());
        $this->getBBApp()->stop();
    }
    
    
}