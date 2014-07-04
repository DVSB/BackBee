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
use BackBuilder\Rest\Controller\UserController;
use BackBuilder\Test\TestCase;
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
    }
    
    public function testLoginAction()
    {
        $request = new Request(array(), array(
            'username' => 'user123',
            'password' => 'password123',
            '_action' => 'loginAction',
            '_controller' => 'BackBuilder\Rest\Controller\UserController',
        ));
        $controller = new UserController($this->bbapp);
        
        $response = $controller->loginAction($request);
        
        $this->assertEquals(204, $response->getStatusCode());
        
    }
    

    protected function tearDown()
    {
        $this->dropDb($this->bbapp);
        $this->bbapp->stop();
    }
}