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

use BackBuilder\Rest\Patcher\OperationBuilder;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use org\bovigo\vfs\vfsStream;

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
        $this->em = $bbapp->getEntityManager();
        
        $this->site = new Site();
        $this->site->setLabel('Test Site')->setServerName('test_server');
        $this->em->persist($this->site);
        
        // permissions
        $this->getContainer()->set('site', $this->site);
        $this->restUser = $this->createAuthUser('page_admin');
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
            ->setPath($this->getBBApp()->getBaseRepository() . '/Layouts/default.twig')
        ;
        
        $em = $this->getEntityManager();
        $em->persist($layout);
        $em->flush();
        
        $this->getAclManager()->insertOrUpdateClassAce(
            new ObjectIdentity('class', 'BackBuilder\NestedNode\Page'), 
            new UserSecurityIdentity('page_admin', 'BackBuilder\Security\Group'), 
            MaskBuilder::MASK_CREATE
        )->insertOrUpdateClassAce(
            $layout,
            new UserSecurityIdentity('page_admin', 'BackBuilder\Security\Group'), 
            MaskBuilder::MASK_VIEW
        );
        
        $response = $this->sendRequest(self::requestPost('/rest/1/page', [
            'title' => 'New Page',
            'url' => 'url',
            'layout_uid' => $layout->getUid()
        ]));
        
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('http://localhost/url', $response->headers->get('Location'));
    }
    
    /**
     * @covers ::putAction
     */
    public function test_putAction()
    {
        $em = $this->getEntityManager();
        
        $layout = new Layout();

        $layout->setLabel('Default')
            ->setSite($this->site)
            ->setDataObject(new \stdClass)
            ->setPath($this->getBBApp()->getBaseRepository() . '/Layouts/default.twig')
        ;
        $em->persist($layout);
        
        // create pages
        $homePage = new Page();
        $homePage
            ->setTitle('Page')
            ->setState(Page::STATE_OFFLINE)
            ->setSite($this->site)
        ;
        $em->persist($homePage);
        
        $em->flush();
        
        
        $this->getAclManager()->insertOrUpdateObjectAce(
            $homePage, 
            new UserSecurityIdentity('page_admin', 'BackBuilder\Security\Group'), 
            ['PUBLISH', 'EDIT']
        )->insertOrUpdateObjectAce(
            $layout,
            new UserSecurityIdentity('page_admin', 'BackBuilder\Security\Group'), 
            ['VIEW']
        );

        $response = $this->sendRequest(self::requestPut('/rest/1/page/' . $homePage->getUid(), [
            'title' => 'New Page',
            'url' => 'url',
            'target' => Page::DEFAULT_TARGET,
            'state' => Page::STATE_ONLINE,
            'layout_uid' => $layout->getUid()
        ]));
        
        $this->assertEquals(204, $response->getStatusCode());
    }
    
    /**
     * @covers ::patchAction
     */
    public function test_patchAction()
    {
        // create page
        $page = (new Page())
            ->setTitle('Page Title')
            ->setState(Page::STATE_OFFLINE)
            ->setSite($this->site)
        ;
        
        $em = $this->getEntityManager();
        $em->persist($page);
        $em->flush();
        
        $this->getAclManager()->insertOrUpdateObjectAce(
            $page, 
            new UserSecurityIdentity('page_admin', 'BackBuilder\Security\Group'), 
            ['PUBLISH', 'EDIT']
        );

        $response = $this->sendRequest(self::requestPatch('/rest/1/page/' . $page->getUid(), (new OperationBuilder())
            ->replace('title', 'New Page Title')
            ->replace('state', Page::STATE_ONLINE)
            ->getOperations()
        ));
        
        $this->assertEquals(204, $response->getStatusCode());
        
        $pageUpdated = $em->getRepository(get_class($page))->find($page->getUid());
        
        $this->assertEquals(Page::STATE_ONLINE, $pageUpdated->getState());
        $this->assertEquals('New Page Title', $pageUpdated->getTitle());
    }
    
    /**
     * @covers ::getCollectionAction
     */
    public function test_getCollectionAction()
    {
        // create pages
        $homePage = new Page();
        $homePage
            ->setTitle('Home Page')
            ->setState(Page::STATE_ONLINE)
            ->setSite($this->site)
        ;
        
        $deletedPage = new Page();
        $deletedPage
            ->setTitle('Deleted')
            ->setState(Page::STATE_DELETED)
            ->setSite($this->site)
        ;
        
        $offlinePage = new Page();
        $offlinePage
            ->setTitle('Offline')
            ->setState(Page::STATE_OFFLINE)
            ->setSite($this->site)
        ;
        
        $onlinePage = new Page();
        $onlinePage
            ->setTitle('Online')
            ->setState(Page::STATE_ONLINE)
            ->setSite($this->site)
        ;
        $onlinePage2 = new Page();
        $onlinePage2
            ->setTitle('Online2')
            ->setState(Page::STATE_ONLINE)
            ->setSite($this->site)
        ;
        $this->em->persist($homePage);
        $this->em->persist($deletedPage);
        $this->em->persist($offlinePage);
        $this->em->persist($onlinePage);
        $this->em->persist($onlinePage2);
        
        $repo = $this->em->getRepository('BackBuilder\NestedNode\Page');
        
        $this->em->flush();
        
        $repo->insertNodeAsFirstChildOf($deletedPage, $homePage);
        $repo->insertNodeAsFirstChildOf($offlinePage, $homePage);
        $repo->insertNodeAsFirstChildOf($onlinePage, $homePage);
        $repo->insertNodeAsFirstChildOf($onlinePage2, $onlinePage);
        
        $this->getAclManager()->insertOrUpdateObjectAce(
            $homePage, 
            new UserSecurityIdentity('page_admin', 'BackBuilder\Security\Group'), 
            MaskBuilder::MASK_VIEW
        );
        
        // no filters - should return online pages by default
        $response1 = $this->sendRequest(self::requestGet('/rest/1/page'));
        $this->assertEquals(200, $response1->getStatusCode());
        $res1 = json_decode($response1->getContent(), true);
        $this->assertInternalType('array', $res1);
        $this->assertCount(3, $res1);
        
        
        // filter by state = offline
        $response2 = $this->sendRequest(self::requestGet('/rest/1/page', ['state' => Page::STATE_OFFLINE]));
        $this->assertEquals(200, $response2->getStatusCode());
        $res2 = json_decode($response2->getContent(), true);
        $this->assertInternalType('array', $res2);
        $this->assertCount(1, $res2);
        $this->assertEquals($offlinePage->getUid(), $res2[0]['uid']);
        
        // filter by parent
        $response3 = $this->sendRequest(self::requestGet('/rest/1/page', ['parent_uid' => $onlinePage->getUid()]));
        $this->assertEquals(200, $response3->getStatusCode());
        $res3 = json_decode($response3->getContent(), true);
        $this->assertInternalType('array', $res3);
        $this->assertCount(1, $res3);
        $this->assertEquals($offlinePage->getUid(), $res3[0]['uid']);
    }
    
}