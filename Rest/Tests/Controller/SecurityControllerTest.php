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

namespace BackBuilder\Security\Tests\Controller;

use BackBuilder\Rest\EventListener\PaginationListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Symfony\Component\HttpFoundation\Request;

use BackBuilder\FrontController\FrontController;
use BackBuilder\Rest\Controller\SecurityController;
use BackBuilder\Test\TestCase;


use BackBuilder\Site\Site,
    BackBuilder\Security\User;


/**
 * Test for AuthController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class SecurityControllerTest extends TestCase
{

    protected $bbapp;
    
    protected function setUp()
    {
        $this->initAutoload();
        $this->bbapp = new \BackBuilder\BBApplication(null, 'test');
        $this->initDb($this->bbapp);
        $this->bbapp->start();
        
        // valid user
        $user = new User();
        $user->setLogin('user123');
        $user->setPassword('password123');
        $user->setActivated(true);
        $this->bbapp->getEntityManager()->persist($user);
        
        
        $this->bbapp->getEntityManager()->flush();
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


    public function testAuthAction_bb_area()
    {
        $controller = $this->getController();
        
        $request = new Request(array(), array(
            'username' => 'user123',
            'password' => 'password123'
        ));
        $response = $controller->authenticateAction('bb_area', $request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $content);
        $this->assertArrayHasKey('nonce', $content);
    }
    
    public function testAuthAction_bb_area_userDoesntExist()
    {
        $controller = $this->getController();
        
        $request = new Request(array(), array(
            'username' => 'userThatDoesntExist',
            'password' => 'password123'
        ));
        $response = $controller->authenticateAction('bb_area', $request);
        
        $this->assertEquals(404, $response->getStatusCode());
    }
    
    public function testAuthAction_bb_area_invalidPassword()
    {
        $controller = $this->getController();
        
        $request = new Request(array(), array(
            'username' => 'user123',
            'password' => 'passwordInvalid'
        ));
        $response = $controller->authenticateAction('bb_area', $request);
        
        $this->assertEquals(401, $response->getStatusCode());
    }
    
    /**
     * 
     */
    public function testAuthAction_invalidFirewall()
    {
        $controller = $this->getController();
        
        $response = $controller->authenticateAction('invalidFirewallName', new Request());
        
        $this->assertEquals(400, $response->getStatusCode());
    }
    
    /**
     * 
     */
    public function testAuthAction_firewallWithoutSupportedContexts()
    {
        $controller = $this->getController();
        
        $response = $controller->authenticateAction('rest_api_area_test', new Request());
        
        $this->assertEquals(400, $response->getStatusCode());
    }
    

    protected function tearDown()
    {
        $this->dropDb($this->bbapp);
        $this->bbapp->stop();
    }
    
    /**
     * 
     * @return 
     */
    protected function getEntityManager()
    {
         return $this->bbapp->getContainer()->get('em');
    }
}