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

use Symfony\Component\HttpFoundation\Request;
use BackBee\Rest\Controller\GroupController;
use BackBee\Security\Group;
use BackBee\Security\User;
use BackBee\Site\Site;
use BackBee\Tests\TestCase;

/**
 * Test for GroupController class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\Controller\GroupController
 * @group Rest
 */
class GroupControllerTest extends TestCase
{
    protected $user;
    protected $site;
    protected $groupEditor;

    protected function setUp()
    {
        $this->initAutoload();
        $bbapp = $this->getBBApp();
        $this->initDb($bbapp);
        $this->initAcl();
        $this->getBBApp()->setIsStarted(true);

        $this->site = new Site();
        $this->site->setLabel('Test Site')->setServerName('test_server');

        $this->groupEditor = new Group();
        $this->groupEditor->setName('groupName');
        $this->groupEditor->setSite($this->site);

        $this->getBBApp()->getContainer()->set('site', $this->site);

        $bbapp->getEntityManager()->persist($this->site);
        $bbapp->getEntityManager()->persist($this->groupEditor);

        $bbapp->getEntityManager()->flush();

        $this->user = $this->createAuthUser($this->groupEditor->getId());
        $bbapp->getEntityManager()->persist($this->user);
        $bbapp->getEntityManager()->flush();
    }

    protected function getController()
    {
        $controller = new GroupController();
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
            'id' => $this->groupEditor->getId(),
            '_action' => 'getCollectionAction',
            '_controller' => 'BackBee\Rest\Controller\GroupController',
        ), array(), array(), array('REQUEST_URI' => '/rest/1/test/')));

        $res = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $res);

        $this->assertCount(1, $res);

        // filter by site
        $response = $this->getBBApp()->getController()->handle(new Request(array(), array(
            'site_uid' => $this->site->getUid(),
        ), array(
            'id' => $this->groupEditor->getId(),
            '_action' => 'getCollectionAction',
            '_controller' => 'BackBee\Rest\Controller\GroupController',
        ), array(), array(), array('REQUEST_URI' => '/rest/1/test/')));

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $res);

        $this->assertCount(1, $res);
        $this->assertEquals($this->site->getUid(), $res[0]['site_uid']);

        // filter by site that has no
        $siteWithNoGroups = new Site();
        $siteWithNoGroups->setLabel('SiteWithNoGroups');
        $this->getBBApp()->getEntityManager()->persist($siteWithNoGroups);
        $this->getBBApp()->getEntityManager()->flush();

        $response = $this->getBBApp()->getController()
        ->handle(new Request(
            [],
            ['site_uid' => $siteWithNoGroups->getUid()],
            [
                'id' => $this->groupEditor->getId(),
                '_action' => 'getCollectionAction',
                '_controller' => 'BackBee\Rest\Controller\GroupController',
            ],
            [],
            [],
            ['REQUEST_URI' => '/rest/1/test/']));

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $res);
        $this->assertCount(0, $res);
    }

    /**
     * @covers ::getCollectionAction
     */
    public function testGetCollectionAction_invalidFilters()
    {
        $controller = $this->getController();

        $response = $this->getBBApp()->getController()->handle(new Request(
            [],
            ['site_uid' => 'thisIsAFakeUid'],
            [
                'id' => $this->groupEditor->getId(),
                '_action' => 'getCollectionAction',
                '_controller' => 'BackBee\Rest\Controller\GroupController',
            ],
            [],
            [],
            ['REQUEST_URI' => '/rest/1/test/']
        ));
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @covers ::getAction
     */
    public function test_getAction()
    {
        $controller = $this->getController();

        $response = $controller->getAction($this->groupEditor);

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $content);

        $this->assertEquals($this->groupEditor->getId(), $content['id']);

        // invalid group id
        $response = $this->getBBApp()->getController()->handle(Request::create('/rest/1/group/13807548404/'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @covers ::deleteAction
     */
    public function testDeleteAction()
    {
        // create user
        $controller = $this->getController();
        $groupId = $this->groupEditor->getId();
        $response = $controller->deleteAction($this->groupEditor);

        $this->assertEquals(204, $response->getStatusCode());

        $groupAfterDelete = $this->getBBApp()->getEntityManager()->getRepository('BackBee\Security\Group')->find($groupId);
        $this->assertTrue(is_null($groupAfterDelete));

        // invalid group id
        $response = $this->getBBApp()->getController()->handle(Request::create('/rest/1/group/13807548404/', 'DELETE'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @covers ::putAction
     */
    public function testPutAction()
    {
        $groupId = $this->groupEditor->getId();

        $controller = $this->getController();

        $data = array(
            'name' => 'newGroupName_CHANGED',
            'site_uid' => $this->site->getUid(),
        );

        $response = $controller->putAction($this->groupEditor, new Request(array(), $data));

        $this->assertEquals(204, $response->getStatusCode());

        $groupUpdated = $this->getBBApp()->getEntityManager()->getRepository('BackBee\Security\Group')->find($groupId);
        $this->assertEquals($data['name'], $groupUpdated->getName());
        $this->assertEquals($data['site_uid'], $groupUpdated->getSite()->getUid());

        // duplicate identifier
        $data = array(
            'name' => 'newGroupName',
            'site_uid' => $this->site->getUid(),
        );

        $controller->postAction(new Request(array(), $data));
        $response = $controller->putAction($this->groupEditor, new Request(array(), $data));
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @covers ::putAction
     */
    public function testPutAction_empty_required_fields()
    {
        $response = $this->getBBApp()->getController()->handle(new Request(array(), array(
        ), array(
            'id' => $this->groupEditor->getId(),
            '_action' => 'putAction',
            '_controller' => 'BackBee\Rest\Controller\GroupController',
        ), array(), array(), array('REQUEST_URI' => '/rest/1/test/')));

        $this->assertEquals(400, $response->getStatusCode());

        $res = json_decode($response->getContent(), true);

        $this->assertContains('Name is required', $res['errors']['name']);
    }

    /**
     * @covers ::postAction
     */
    public function testPostAction()
    {
        $controller = $this->getController();

        $data = array(
            'name' => 'newGroupName',
            'site_uid' => $this->site->getUid(),
        );

        $response = $controller->postAction(new Request(array(), $data));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $res = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $res);
        $this->assertEquals($data['name'], $res['name']);
        $this->assertEquals($data['site_uid'], $res['site_uid']);

        $this->assertArrayHasKey('id', $res);

        $group = $this->getBBApp()->getEntityManager()->getRepository('BackBee\Security\Group')->find($res['id']);
        $this->assertInstanceOf('BackBee\Security\Group', $group);

        // duplicate identifier
        $data = array(
            'name' => 'newGroupName',
            'site_uid' => $this->site->getUid(),
        );

        $response = $controller->postAction(new Request(array(), $data));
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @covers ::postAction
     * @expectedException \BackBee\Rest\Exception\ValidationException
     */
    public function test_postAction_invalidSite()
    {
        $controller = $this->getController();
        // invalide site
        $data = array(
            'name' => 'newGroupName',
            'site_uid' => 'SiteUidThatDoesntExist',
        );

        $response = $this->getController()->postAction(new Request(array(), $data));
    }

    /**
     * @covers ::postAction
     */
    public function test_postAction_missing_required_fields()
    {
        $response = $this->getBBApp()->getController()->handle(new Request(array(), array(), array(
            '_action' => 'postAction',
            '_controller' => 'BackBee\Rest\Controller\GroupController',
        ), array(), array(), array('REQUEST_URI' => '/rest/1/test/')));

        $this->assertEquals(400, $response->getStatusCode());

        $res = json_decode($response->getContent(), true);

        $this->assertContains('Name is required', $res['errors']['name']);
    }

    protected function tearDown()
    {
        $this->dropDb($this->getBBApp());
        $this->getBBApp()->stop();
    }
}
