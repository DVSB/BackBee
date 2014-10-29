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
    BackBuilder\Site\Site,
    BackBuilder\NestedNode\Page;

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
        $em = $bbapp->getEntityManager();
        $this->site = new Site();
        $this->site->setLabel('Test Site')->setServerName('test_server');
        
        $this->groupEditor = new Group();
        $this->groupEditor->setName('groupName');
        $this->groupEditor->setSite($this->site);
        $this->groupEditor->setIdentifier('GROUP_ID');
        
        $em->persist($this->site);
        $em->persist($this->groupEditor);
        
        
        $this->deletedPage = new Page();
        $this->deletedPage
            ->setTitle('Deleted')
            ->setState(Page::STATE_DELETED)
            ->setSite($this->site)
        ;
        
        $this->offlinePage = new Page();
        $this->offlinePage
            ->setTitle('Offline')
            ->setState(Page::STATE_OFFLINE)
            ->setSite($this->site)
        ;
        
        $this->onlinePage = new Page();
        $this->onlinePage
            ->setTitle('Online')
            ->setState(Page::STATE_ONLINE)
            ->setSite($this->site)
        ;
        
        $em->persist($this->deletedPage);
        $em->persist($this->offlinePage);
        $em->persist($this->onlinePage);
        
        
        $em->flush();
    }
    
    private function getController()
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