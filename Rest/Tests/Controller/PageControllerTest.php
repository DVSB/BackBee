<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Rest\Tests\Controller;

use BackBee\Rest\Controller\PageController;
use BackBee\Rest\Test\RestTestCase;
use BackBee\Site\Site;
use BackBee\NestedNode\Page;
use BackBee\Site\Layout;
use BackBee\Security\Acl\Permission\MaskBuilder;
use BackBee\Rest\Patcher\OperationBuilder;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * Test for PageController class
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\Controller\PageController
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
        self::$restUser = $this->createAuthUser('page_admin');
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
    public function testPostAction()
    {
        $layout = new Layout();

        $layout->setLabel('Default')
            ->setSite($this->site)
            ->setDataObject(new \stdClass())
            ->setPath($this->getBBApp()->getBaseRepository().'/Layouts/default.twig')
        ;

        $em = $this->getEntityManager();
        $em->persist($layout);
        $em->flush();

        $this->getAclManager()->insertOrUpdateClassAce(
            new ObjectIdentity('class', 'BackBee\NestedNode\Page'),
            new UserSecurityIdentity('page_admin', 'BackBee\Security\Group'),
            MaskBuilder::MASK_CREATE
        )->insertOrUpdateClassAce(
            $layout,
            new UserSecurityIdentity('page_admin', 'BackBee\Security\Group'),
            MaskBuilder::MASK_VIEW
        );

        $response = $this->sendRequest(self::requestPost('/rest/1/page', [
            'title' => 'New Page',
            'url' => 'url',
            'layout_uid' => $layout->getUid()
        ]));

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @covers ::putAction
     */
    public function testPutAction()
    {
        $em = $this->getEntityManager();

        $layout = new Layout();

        $layout->setLabel('Default')
            ->setSite($this->site)
            ->setDataObject(new \stdClass())
            ->setPath($this->getBBApp()->getBaseRepository().'/Layouts/default.twig')
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
            new UserSecurityIdentity('page_admin', 'BackBee\Security\Group'),
            ['PUBLISH', 'EDIT']
        )->insertOrUpdateObjectAce(
            $layout,
            new UserSecurityIdentity('page_admin', 'BackBee\Security\Group'),
            ['VIEW']
        );

        $response = $this->sendRequest(self::requestPut('/rest/1/page/'.$homePage->getUid(), [
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
    public function testPatchAction()
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
            new UserSecurityIdentity('page_admin', 'BackBee\Security\Group'),
            ['PUBLISH', 'EDIT']
        );

        $response = $this->sendRequest(self::requestPatch('/rest/1/page/'.$page->getUid(), (new OperationBuilder())
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
    public function testGetCollectionAction()
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

        $repo = $this->em->getRepository('BackBee\NestedNode\Page');

        $repo->insertNodeAsFirstChildOf($deletedPage, $homePage);
        $repo->insertNodeAsFirstChildOf($offlinePage, $homePage);
        $repo->insertNodeAsFirstChildOf($onlinePage, $homePage);
        $repo->insertNodeAsFirstChildOf($onlinePage2, $onlinePage);

        $this->em->flush();

        $this->getAclManager()->insertOrUpdateObjectAce(
            $homePage,
            new UserSecurityIdentity('page_admin', 'BackBee\Security\Group'),
            MaskBuilder::MASK_VIEW
        );

        // no filters - should return current site root page
        $response1 = $this->sendRequest(self::requestGet('/rest/1/page'));
        $this->assertEquals(200, $response1->getStatusCode());
        $res1 = json_decode($response1->getContent(), true);
        $this->assertInternalType('array', $res1);
        $this->assertCount(1, $res1);
        $this->assertEquals($homePage->getUid(), $res1[0]['uid']);

        // filter by parent
        $response2 = $this->sendRequest(self::requestGet('/rest/1/page', array('parent_uid' => $homePage->getUid())));
        $this->assertEquals(200, $response2->getStatusCode());
        $res2 = json_decode($response2->getContent(), true);
        $this->assertInternalType('array', $res2);
        $this->assertCount(3, $res2);
        // $this->assertEquals($offlinePage->getUid(), $res3[0]['uid']);

        // filter by state = offline
        $response3 = $this->sendRequest(self::requestGet('/rest/1/page', array(
            'parent_uid' => $homePage->getUid(),
            'state'      => array(Page::STATE_OFFLINE),
        )));

        $this->assertEquals(200, $response3->getStatusCode());
        $res3 = json_decode($response3->getContent(), true);
        $this->assertInternalType('array', $res3);
        $this->assertCount(1, $res3);
        $this->assertEquals($offlinePage->getUid(), $res3[0]['uid']);
    }
}
