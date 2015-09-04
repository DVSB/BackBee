<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Rest\Tests\Controller;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use BackBee\NestedNode\Page;
use BackBee\Rest\Controller\PageController;
use BackBee\Rest\Patcher\OperationBuilder;
use BackBee\Rest\Test\RestTestCase;
use BackBee\Security\Acl\Permission\MaskBuilder;
use BackBee\Site\Layout;
use BackBee\Site\Site;

/**
 * Test for PageController class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\Controller\PageController
 * @group Rest
 */
class PageControllerTest extends RestTestCase
{
    protected $site;
    protected $group_id;

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
        $this->group_id = self::$restUser->getGroups()[0]->getId();

        $this->em->flush();
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
            new ObjectIdentity('all', 'BackBee\NestedNode\Page'),
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
            MaskBuilder::MASK_CREATE
        )->insertOrUpdateClassAce(
            $layout,
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
            MaskBuilder::MASK_VIEW
        );

        $response = $this->sendRequest(self::requestPost('/rest/1/page', [
            'title' => 'New Page',
            'url' => 'url',
            'layout_uid' => $layout->getUid(),
        ]));

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testCloneAction()
    {
        $em = $this->getEntityManager();
        $layout = new Layout();

        $layout->setLabel('Default')
            ->setSite($this->site)
            ->setDataObject(new \stdClass())
            ->setPath($this->getBBApp()->getBaseRepository().'/Layouts/default.twig')
        ;

        $em->persist($layout);
        $em->flush();

        $rootPage = (new Page())
            ->setTitle('Page Root')
            ->setSite($this->site)
            ->setLayout($layout)
        ;

        $em->persist($rootPage);
        $em->flush();

        // create page
        $clonePage = (new Page())
            ->setTitle('Page Title')
            ->setSite($this->site)
            ->setLayout($layout)
            ->setParent($rootPage)
        ;

        $em->persist($clonePage);
        $em->flush();

        $this->getAclManager()->insertOrUpdateObjectAce(
            new ObjectIdentity('all', 'BackBee\NestedNode\Page'),
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
            ['VIEW', 'CREATE']
        )->insertOrUpdateObjectAce(
            $rootPage,
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
            ['VIEW', 'EDIT']
        )->insertOrUpdateObjectAce(
            $layout,
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
            ['VIEW']
        )->insertOrUpdateObjectAce(
            $this->site,
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
            ['VIEW']
        );

        $response = $this->sendRequest(self::requestPost('/rest/1/page/'.$clonePage->getUid().'/clone', [
            'title' => 'A new title'
            ])
        );

        $this->assertTrue($response->headers->has('location'));
        $this->assertEquals('/a-new-title', $response->headers->get('BB-PAGE-URL'));
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
            ->setSite($this->site)
        ;
        $em->persist($homePage);

        $em->flush();

        $this->getAclManager()->insertOrUpdateObjectAce(
            $homePage,
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
            ['PUBLISH', 'EDIT']
        )->insertOrUpdateObjectAce(
            $layout,
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
            ['VIEW']
        );

        $response = $this->sendRequest(self::requestPut('/rest/1/page/'.$homePage->getUid(), [
            'title' => 'New Page',
            'url' => 'url',
            'target' => Page::DEFAULT_TARGET,
            'state' => Page::STATE_ONLINE,
            'layout_uid' => $layout->getUid(),
        ]));

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @covers ::getCollectionAction
     */
    public function testPutCollectionAction()
    {
        $pages = $this->initializeTestGetCollectionAction();

        // change state with Insufficient rigth
        $response = $this->sendRequest(self::requestPut('/rest/1/page', [
            ['uid' => $pages['offline']->getUid(), 'state' => 'online'],
        ]));
        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertEquals(403, $res[0]['statusCode']);

        $builder = new MaskBuilder();
        $builder
            ->add('VIEW')
            ->add('PUBLISH')
            ->add('EDIT');

        $this->getAclManager()->insertOrUpdateObjectAce(
            $pages['home'],
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
            $builder->get()
        );

        // change state to onlinne
        $response1 = $this->sendRequest(self::requestPut('/rest/1/page', [
            ['uid' => $pages['offline']->getUid(), 'state' => 'online'],
            ['uid' => $pages['online']->getUid(), 'state' => 'online'],
        ]));
        $this->assertEquals(200, $response1->getStatusCode());

        $res1 = json_decode($response1->getContent(), true);
        $this->assertInternalType('array', $res1);
        $this->assertCount(2, $res1);
        $this->assertEquals(200, $res1[0]['statusCode']);
        $this->assertEquals(304, $res1[1]['statusCode']);
        $this->em->refresh($pages['offline']);
        $this->assertEquals(Page::STATE_ONLINE, $pages['offline']->getState());

        // change state to offline
        $response2 = $this->sendRequest(self::requestPut('/rest/1/page', [
            ['uid' => $pages['offline']->getUid(), 'state' => 'offline'],
            ['uid' => $pages['online']->getUid(), 'state' => 'offline'],
        ]));
        $this->assertEquals(200, $response2->getStatusCode());
        $res2 = json_decode($response2->getContent(), true);
        $this->assertInternalType('array', $res2);
        $this->assertCount(2, $res2);
        $this->assertEquals(200, $res2[0]['statusCode']);
        $this->assertEquals(200, $res2[1]['statusCode']);
        $this->em->refresh($pages['offline']);
        $this->assertEquals(Page::STATE_OFFLINE, $pages['offline']->getState());
        $this->assertEquals(Page::STATE_OFFLINE, $pages['online']->getState());

        // change parent
        $response3 = $this->sendRequest(self::requestPut('/rest/1/page', [
            ['uid' => $pages['offline']->getUid(), 'parent_uid' => $pages['online']->getUid()],
        ]));
        $this->assertEquals(200, $response3->getStatusCode());
        $res3 = json_decode($response3->getContent(), true);
        $this->assertInternalType('array', $res3);
        $this->assertCount(1, $res3);
        $this->assertEquals(200, $res3[0]['statusCode']);
        $this->em->refresh($pages['offline']);
        $this->assertEquals($pages['online']->getUid(), $pages['offline']->getParent()->getUid());

        // soft delete
        $response4 = $this->sendRequest(self::requestPut('/rest/1/page', [
            ['uid' => $pages['offline']->getUid(), 'state' => 'delete'],
        ]));
        $this->assertEquals(200, $response4->getStatusCode());
        $res4 = json_decode($response4->getContent(), true);
        $this->assertInternalType('array', $res4);
        $this->assertCount(1, $res4);
        $this->assertEquals(200, $res4[0]['statusCode']);
        $this->em->refresh($pages['offline']);
        $this->assertEquals(4, $pages['offline']->getState());

        // hard delete not working yet
        //
    }

    public function testGetAncestorsAction()
    {
        $pages = $this->initializeTestGetCollectionAction();

        $homePage =  $pages["home"];
        $deletePage = $pages["delete"];
        $online2Page = $pages["online2"];

        /* home page has no ancestors */
        $response1 = $this->sendRequest(self::requestGet("/rest/1/page/" . $homePage->getUid() . "/ancestors"));
        $this->assertEquals(200, $response1->getStatusCode());
        $resContent1 =  json_decode($response1->getContent(), true);
        $this->assertInternalType('array', $resContent1);
        $this->assertCount(0, $resContent1);

        /*delete page has one ancestor*/
        $response2 = $this->sendRequest(self::requestGet("/rest/1/page/" . $deletePage->getUid() . "/ancestors"));
        $this->assertEquals(200, $response2->getStatusCode());
        $restContent2 = json_decode($response2->getContent(), true);
        $this->assertInternalType('array', $restContent2);
        $this->assertCount(1, $restContent2);

        /*online2 page has two ancestor*/
        $response3 = $this->sendRequest(self::requestGet("/rest/1/page/" . $online2Page->getUid() . "/ancestors"));
        $this->assertEquals(200, $response3->getStatusCode());
        $restContent3 = json_decode($response3->getContent(), true);
        $this->assertInternalType('array', $restContent3);
        $this->assertCount(2, $restContent3);
    }



    public function testPutCollectionActionHardDelete()
    {
        $this->markTestSkipped('To do this test work we need to change TestCase system to BackBeeTestCase');
        $response5 = $this->sendRequest(self::requestPut('/rest/1/page', [
            ['uid' => $pages['offline']->getUid(), 'state' => 'delete'],
        ]));

        $this->assertEquals(200, $response5->getStatusCode());
        $res5 = json_decode($response5->getContent(), true);
        $this->assertInternalType('array', $res5);
        $this->assertCount(1, $res5);
        $this->assertEquals(200, $res5[0]['statusCode']);
        $page = $this->em->find('BackBee\NestedNode\Page', $pages['offline']->getUid());
        $this->assertNull($page);
    }

    /**
     * @covers ::patchAction
     */
    public function testPatchAction()
    {
        // create page
        $page = (new Page())
            ->setTitle('Page Title')
            ->setSite($this->site)
        ;

        $em = $this->getEntityManager();
        $em->persist($page);
        $em->flush();

        $this->getAclManager()->insertOrUpdateObjectAce(
            $page,
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
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
     * Generating test tree
     *
     * root
     *   |_online
     *   |     |_online 2
     *   |_offline
     *   |     |_delete 2
     *   |_delete
     *
     * @return Array tree collection
     */
    private function initializeTestGetCollectionAction()
    {
        // create pages
        $pages = [];
        $pages['home'] = new Page();
        $pages['home']
            ->setTitle('Home Page')
            ->setSite($this->site)
        ;

        $pages['delete'] = new Page();
        $pages['delete']
            ->setTitle('Deleted')
            ->setSite($this->site)
        ;

        $pages['online'] = new Page();
        $pages['online']
            ->setTitle('Online')
            ->setSite($this->site)
        ;

        $pages['online2'] = new Page();
        $pages['online2']
            ->setTitle('Online 2')
            ->setSite($this->site)
        ;

        $pages['offline'] = new Page();
        $pages['offline']
            ->setTitle('Offline')
            ->setSite($this->site)
        ;

        $pages['delete2'] = new Page();
        $pages['delete2']
            ->setTitle('Deleted 2')
            ->setSite($this->site)
        ;
        $this->em->persist($pages['home']);
        $this->em->flush($pages['home']);

        $repo = $this->em->getRepository('BackBee\NestedNode\Page');

        $repo->insertNodeAsFirstChildOf($pages['delete'], $pages['home']);
        $pages['delete']->setState(Page::STATE_DELETED);
        $this->em->persist($pages['delete']);
        $this->em->flush($pages['delete']);
        $this->refreshEntities($repo);

        $repo->insertNodeAsFirstChildOf($pages['offline'], $pages['home'], true);
        $pages['offline']->setState(Page::STATE_OFFLINE);
        $this->em->persist($pages['offline']);
        $this->em->flush($pages['offline']);
        $this->refreshEntities($repo);

        $repo->insertNodeAsFirstChildOf($pages['online'], $pages['home'], true);
        $pages['online']->setState(Page::STATE_ONLINE);
        $this->em->persist($pages['online']);
        $this->em->flush($pages['online']);
        $this->refreshEntities($repo);

        $repo->insertNodeAsFirstChildOf($pages['online2'], $pages['online']);
        $pages['online2']->setState(Page::STATE_ONLINE);
        $this->em->persist($pages['online2']);
        $this->em->flush($pages['online2']);
        $this->refreshEntities($repo);

        $repo->insertNodeAsFirstChildOf($pages['delete2'], $pages['offline']);
        $pages['delete2']->setState(Page::STATE_DELETED);
        $this->em->persist($pages['delete2']);
        $this->em->flush($pages['delete2']);
        $this->refreshEntities($repo);

        $this->getAclManager()->insertOrUpdateObjectAce(
            $pages['home'],
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
            MaskBuilder::MASK_VIEW
        );

        return $pages;
    }

    private function refreshEntities($repo)
    {
        foreach ($repo->findAll() as $value) {
            $this->em->refresh($value);
        }
    }

    /**
     * @covers ::getCollectionAction
     */
    public function testGetCollectionActionVersion1()
    {
        $pages = $this->initializeTestGetCollectionAction();

        // no filters - should return current site root page
        $response1 = $this->sendRequest(self::requestGet('/rest/1/page'));
        $this->assertEquals(200, $response1->getStatusCode());
        $res1 = json_decode($response1->getContent(), true);
        $this->assertInternalType('array', $res1);
        $this->assertCount(1, $res1);
        $this->assertEquals($pages['home']->getUid(), $res1[0]['uid']);

        // filter by parent
        $response2 = $this->sendRequest(self::requestGet('/rest/1/page', ['parent_uid' => $pages['home']->getUid()]));
        $this->assertEquals(200, $response2->getStatusCode());
        $res2 = json_decode($response2->getContent(), true);
        $this->assertInternalType('array', $res2);
        $this->assertCount(3, $res2);
        $this->assertEquals($pages['online']->getUid(), $res2[0]['uid']);
        $this->assertEquals($pages['offline']->getUid(), $res2[1]['uid']);
        $this->assertEquals($pages['delete']->getUid(), $res2[2]['uid']);

        $this->assertFalse($res2[1]['has_children'], 'Test if offline has non deleted children');

        // filter by state = offline
        $response3 = $this->sendRequest(self::requestGet('/rest/1/page', array(
            'parent_uid' => $pages['home']->getUid(),
            'state'      => array(Page::STATE_OFFLINE),
        )));

        $this->assertEquals(200, $response3->getStatusCode());
        $res3 = json_decode($response3->getContent(), true);
        $this->assertInternalType('array', $res3);
        $this->assertCount(1, $res3);
        $this->assertEquals($pages['offline']->getUid(), $res3[0]['uid']);
    }

    /**
     * @covers ::getCollectionAction
     */
    public function testGetCollectionAction()
    {
        $pages = $this->initializeTestGetCollectionAction();

        // no filters - should return all site pages
        $response1 = $this->sendRequest(self::requestGet('/rest/2/page'));
        $this->assertEquals(200, $response1->getStatusCode());
        $res1 = json_decode($response1->getContent(), true);
        $this->assertInternalType('array', $res1);
        $this->assertCount(6, $res1);
        $this->assertEquals($pages['home']->getUid(), $res1[0]['uid']);

        // root filters - should return current site root page
        $response1 = $this->sendRequest(self::requestGet('/rest/2/page', ['root' => '']));
        $this->assertEquals(200, $response1->getStatusCode());
        $res1 = json_decode($response1->getContent(), true);
        $this->assertInternalType('array', $res1);
        $this->assertCount(1, $res1);
        $this->assertEquals($pages['home']->getUid(), $res1[0]['uid']);

        // filter by parent
        $response2 = $this->sendRequest(self::requestGet('/rest/2/page', ['parent_uid' => $pages['home']->getUid()]));
        $this->assertEquals(200, $response2->getStatusCode());
        $res2 = json_decode($response2->getContent(), true);
        $this->assertInternalType('array', $res2);
        $this->assertCount(5, $res2);

        $this->assertEquals($pages['online']->getUid(), $res2[0]['uid'], 'filter by parent online');
        $this->assertEquals($pages['offline']->getUid(), $res2[1]['uid'], 'filter by parent offline');
        $this->assertEquals($pages['delete']->getUid(), $res2[2]['uid'], 'filter by parent online2');
        $this->assertEquals($pages['online2']->getUid(), $res2[3]['uid'], 'filter by parent ofline 2');
        $this->assertEquals($pages['delete2']->getUid(), $res2[4]['uid'], 'filter by parent delete 2');

        // filter by title
        $response2 = $this->sendRequest(self::requestGet('/rest/2/page', ['title' => 'online']));
        $this->assertEquals(200, $response2->getStatusCode());
        $res2 = json_decode($response2->getContent(), true);
        $this->assertInternalType('array', $res2);
        $this->assertCount(2, $res2);
        $this->assertEquals($pages['online']->getUid(), $res2[0]['uid'], 'filter by title online');
        $this->assertEquals($pages['online2']->getUid(), $res2[1]['uid'], 'filter by title online2');

        // filter by parent and level offset
        $response2 = $this->sendRequest(self::requestGet('/rest/2/page', ['parent_uid' => $pages['home']->getUid(), 'level_offset' => 1]));
        $this->assertEquals(200, $response2->getStatusCode());
        $res2 = json_decode($response2->getContent(), true);
        $this->assertInternalType('array', $res2);
        $this->assertCount(3, $res2);
        $this->assertEquals($pages['online']->getUid(), $res2[0]['uid']);
        $this->assertEquals($pages['offline']->getUid(), $res2[1]['uid']);
        $this->assertEquals($pages['delete']->getUid(), $res2[2]['uid']);

        // filter by parent and level offset
        $response2 = $this->sendRequest(self::requestGet('/rest/2/page', ['parent_uid' => $pages['home']->getUid(), 'has_children' => '']));
        $this->assertEquals(200, $response2->getStatusCode());
        $res2 = json_decode($response2->getContent(), true);
        $this->assertInternalType('array', $res2);
        $this->assertCount(1, $res2);
        $this->assertEquals($pages['online']->getUid(), $res2[0]['uid']);

        // filter by state = offline
        $response3 = $this->sendRequest(self::requestGet('/rest/1/page', array(
            'parent_uid' => $pages['home']->getUid(),
            'state'      => array(Page::STATE_OFFLINE),
        )));

        $this->assertEquals(200, $response3->getStatusCode());
        $res3 = json_decode($response3->getContent(), true);
        $this->assertInternalType('array', $res3);
        $this->assertCount(1, $res3);
        $this->assertEquals($pages['offline']->getUid(), $res3[0]['uid']);
    }

    public function testGetAction()
    {
        // create pages
        $now = new \DateTime();

        $homePage = new Page();
        $homePage
            ->setTitle('Home Page')
            ->setState(Page::STATE_ONLINE)
            ->setSite($this->site)
            ->setModified($now)
        ;

        $this->em->persist($homePage);
        $this->em->flush($homePage);

        $this->getAclManager()->insertOrUpdateObjectAce(
            $homePage,
            new UserSecurityIdentity($this->group_id, 'BackBee\Security\Group'),
            MaskBuilder::MASK_VIEW
        );

        // no filters - should return current site root page
        $response = $this->sendRequest(self::requestGet('/rest/1/page/'. $homePage->getUid()));
        $this->assertTrue($response->isOk(), sprintf('HTTP 200 expected, HTTP %s returned.', $response->getStatusCode()));
        $pageProperties = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $pageProperties);
        $this->assertCount(23, $pageProperties);
        $this->assertEquals($homePage->getUid(), $pageProperties['uid']);
        $this->assertEquals($now->getTimestamp(), $pageProperties['modified']);
    }

    protected function tearDown()
    {
        $this->dropDb($this->getBBApp());
        $this->getBBApp()->stop();
    }
}
