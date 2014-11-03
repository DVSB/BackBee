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

use BackBuilder\Rest\Controller\PageController;
use BackBuilder\Rest\Test\RestTestCase;


use BackBuilder\Site\Site,
    BackBuilder\NestedNode\Page,
    BackBuilder\Site\Layout;

use BackBuilder\Security\Acl\Permission\MaskBuilder;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity;

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
class PageControllerTest extends RestTestCase
{
    
    
    protected $site;
    
    protected function setUp()
    {
        $this->initAutoload();
        $bbapp = $this->getBBApp();
        
        $this->initDb($bbapp);
        $this->initAcl();
        $this->getBBApp()->setIsStarted(true);
        $em = $bbapp->getEntityManager();
        
        $this->site = new Site();
        $this->site->setLabel('Test Site')->setServerName('test_server');
        $em->persist($this->site);
        
        // create ACEs
        $this->getContainer()->set('site', $this->site);
        $this->restUser = $this->createAuthUser('page_admin');
        $this->getAclManager()->insertOrUpdateObjectAce(
            new ObjectIdentity('class', 'BackBuilder\NestedNode\Page'), 
            new UserSecurityIdentity('page_admin', 'BackBuilder\Security\Group'), 
            MaskBuilder::MASK_OWNER
        );
        
        // create pages
        $this->homePage = new Page();
        $this->homePage
            ->setTitle('Home Page')
            ->setState(Page::STATE_ONLINE)
            ->setSite($this->site)
        ;
        
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
        $this->onlinePage2 = new Page();
        $this->onlinePage2
            ->setTitle('Online2')
            ->setState(Page::STATE_ONLINE)
            ->setSite($this->site)
        ;
        $em->persist($this->homePage);
        $em->persist($this->deletedPage);
        $em->persist($this->offlinePage);
        $em->persist($this->onlinePage);
        $em->persist($this->onlinePage2);
        
        $repo = $em->getRepository('BackBuilder\NestedNode\Page');
        
        $em->flush();
        
        $repo->insertNodeAsFirstChildOf($this->deletedPage, $this->homePage);
        $repo->insertNodeAsFirstChildOf($this->offlinePage, $this->homePage);
        $repo->insertNodeAsFirstChildOf($this->onlinePage, $this->homePage);
        $repo->insertNodeAsFirstChildOf($this->onlinePage2, $this->onlinePage);
    }
    
    private function getController()
    {
        $controller = new PageController();
        $controller->setContainer($this->getBBApp()->getContainer());
        
        return $controller;
    }

    /**
     * @covers ::postAction
     */
    public function test_postAction()
    {
        $layout = new Layout();
        
        $layout->setLabel('Default')
            ->setSite($this->site)
            ->setDataObject(new \stdClass)
        ;

        $em = $this->getEntityManager();
        $em->persist($layout);
        $em->flush();
        $response = $this->sendRequest(self::requestPost('/rest/1/page', [
            'title' => 'New Page',
            'layout_uid' => $layout->getUid()
        ]));
        
        $aclManager = $this->getBBApp()->getContainer()->get("security.acl_manager");
        
        $this->assertEquals(201, $response->getStatusCode());
        
        $res = json_decode($response->getContent(), true);
    }
    
    /**
     * @covers ::getCollectionAction
     */
    public function test_getCollectionAction()
    {
        return;
        $controller = $this->getController();
        
        // no filters - should return online pages by default
        $response = $this->sendRequest(self::requestGet('/rest/1/page'));
        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $res);
        $this->assertCount(3, $res);
        
        
        // filter by state = offline
        $response = $this->sendRequest(self::requestGet('/rest/1/page', ['state' => Page::STATE_OFFLINE]));
        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertEquals($this->offlinePage->getUid(), $res[0]['uid']);
        
        // filter by parent
        $response = $this->sendRequest(self::requestGet('/rest/1/page', ['parent_uid' => $this->onlinePage->getUid()]));
        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertEquals($this->offlinePage2->getUid(), $res[0]['uid']);
    }
    
    
    
    
}