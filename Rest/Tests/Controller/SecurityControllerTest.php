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
use BackBuilder\Rest\Controller\AuthController;
use BackBuilder\Test\TestCase;


use BackBuilder\Site\Site,
    BackBuilder\Site\Layout;

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
        
        
        
        $this->bbapp->getEntityManager()->flush();
    }
    
    protected function getController()
    {
        $controller = new SecurityController();
        $controller->setContainer($this->bbapp->getContainer());
        
        return $controller;
    }


    public function testAuthAction()
    {
        
        
    }
    

    protected function tearDown()
    {
        $this->dropDb($this->bbapp);
        $this->bbapp->stop();
    }
    
    /**
     * 
     * @return type
     */
    protected function getEntityManager()
    {
         return $this->bbapp->getContainer()->get('em');
    }
}