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
use BackBuilder\Rest\Controller\SiteController;
use BackBuilder\Tests\TestCase;


use BackBuilder\Site\Site,
    BackBuilder\Site\Layout;

/**
 * Test for UserController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class SiteControllerTest extends TestCase
{

    protected $bbapp;
    
    /**
     *
     * @var Site
     */
    protected $site;
    
    /**
     *
     * @var Layout
     */
    protected $layout;
    
    protected function setUp()
    {
        $this->initAutoload();
        $this->bbapp = $this->getBBApp();
        $this->initDb($this->bbapp);
        $this->bbapp->start();
        
        // craete site
        $this->site = new Site();
        $this->site->setLabel('sitelabel');
        $this->site->setServerName('www.example.org');
        
        
        // craete layout
        $this->layout = new Layout();
        
        $this->layout->setSite($this->site);
        $this->layout->setLabel('defaultLayoutLabel');
        $this->layout->setPath($this->bbapp->getBBDir() . '/Rest/Tests/Fixtures/Controller/defaultLayout.html.twig');
        $this->layout->setData(json_encode(array(
            'templateLayouts' => array(
                'title' => 'zone_1234567'
            )
        )));
        
        $this->site->addLayout($this->layout);
        $this->bbapp->getEntityManager()->persist($this->layout);
        $this->bbapp->getEntityManager()->persist($this->site);
        
        $this->bbapp->getEntityManager()->flush();
    }
    
    protected function getController()
    {
        $controller = new SiteController();
        $controller->setContainer($this->bbapp->getContainer());
        
        return $controller;
    }


    public function testGetLayoutsAction()
    {
        $site = $this->getEntityManager()->getRepository('BackBuilder\Site\Site')->find($this->site->getUid());
        $layout = $this->getEntityManager()->getRepository('BackBuilder\Site\layout')->find($this->layout->getUid());
        //var_dump($layout);exit;
        
        $request = new Request(array(), array(
            'id' => $this->site->getUid(),
        ));
        $request->headers->set('Accept', 'application/json');
        
        $controller = $this->getController();
        $response = $controller->getLayoutsAction($this->site->getUid(), $request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        
        $content = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $content);
        
        $this->assertCount(1, $content);
        
        $this->assertEquals($this->layout->getUid(), $content[0]['uid']);
        
    }
    

    protected function tearDown()
    {
        $this->dropDb();
        $this->bbapp->stop();
    }
    
    /**
     * 
     * @return type
     */
    protected function getEntityManager()
    {
         return $this->getBBApp()->getContainer()->get('em');
    }
}