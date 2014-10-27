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

use Symfony\Component\HttpFoundation\Request;

use BackBuilder\Rest\Controller\PageController;
use BackBuilder\Tests\TestCase;


use BackBuilder\Security\User,
    BackBuilder\Security\Group,
    BackBuilder\Site\Site;

/**
 * Test for PageController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 * 
 * @coversDefaultClass \BackBuilder\Rest\Controller\PageController
 */
class PageControllerTest extends TestCase
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
        
        $this->site = new Site();
        $this->site->setLabel('Test Site')->setServerName('test_server');
        
        $this->groupEditor = new Group();
        $this->groupEditor->setName('groupName');
        $this->groupEditor->setSite($this->site);
        $this->groupEditor->setIdentifier('GROUP_ID');
        
        $bbapp->getEntityManager()->persist($this->site);
        $bbapp->getEntityManager()->persist($this->groupEditor);
        
        $bbapp->getEntityManager()->flush();
        
    }
    
    protected function getController()
    {
        $controller = new PageController();
        $controller->setContainer($this->getBBApp()->getContainer());
        
        return $controller;
    }

    
    /**
     * @covers ::getCollectionAction
     */
    public function testGetCollectionAction()
    {
        $controller = $this->getController();
        
        // no filters
        $response = $this->getBBApp()->getController()->handle(new Request(array(), array(
        ), array(
            '_action' => 'getCollectionAction',
            '_controller' => 'BackBuilder\Rest\Controller\PageController'
        ), array(), array(), array('REQUEST_URI' => '/rest/1/test/') ));
        
        $res = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        
        // filter by state
        $response = $this->getBBApp()->getController()->handle(new Request(array(), array(
            'site_uid' => $this->site->getUid()
        ), array(
            'state' => 1,
            '_action' => 'getCollectionAction',
            '_controller' => 'BackBuilder\Rest\Controller\PageController'
        ), array(), array(), array('REQUEST_URI' => '/rest/1/test/') ));
        
        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertEquals($this->site->getUid(), $res[0]['site_uid']);
        
    }
    
}