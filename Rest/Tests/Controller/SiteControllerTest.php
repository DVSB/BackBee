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
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use BackBee\Rest\Controller\SiteController;
use BackBee\Security\Acl\Permission\MaskBuilder;
use BackBee\Site\Layout;
use BackBee\Site\Site;
use BackBee\Tests\TestCase;

/**
 * Test for UserController class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\Controller\SiteController
 * @group Rest
 */
class SiteControllerTest extends TestCase
{
    protected $bbapp;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var Layout
     */
    protected $layout;

    protected function setUp()
    {
        $this->initAutoload();
        $this->bbapp = $this->getBBApp();
        $this->initDb($this->bbapp);
        $this->initAcl();
        $this->bbapp->start();

        // craete site
        $this->site = new Site();
        $this->site->setLabel('sitelabel');
        $this->site->setServerName('www.example.org');

        // craete layout
        $this->layout = new Layout();

        $this->layout->setSite($this->site);
        $this->layout->setLabel('defaultLayoutLabel');
        $this->layout->setPath($this->bbapp->getBBDir().'/Rest/Tests/Fixtures/Controller/defaultLayout.html.twig');
        $this->layout->setData(json_encode(array(
            'templateLayouts' => array(
                'title' => 'zone_1234567',
            ),
        )));

        $this->site->addLayout($this->layout);
        $this->getEntityManager()->persist($this->layout);
        $this->getEntityManager()->persist($this->site);

        $this->getEntityManager()->flush();
    }

    protected function getController()
    {
        $controller = new SiteController();
        $controller->setContainer($this->bbapp->getContainer());

        return $controller;
    }

    /**
     * @covers ::getLayoutsAction
     */
    public function testGetLayoutsAction()
    {
        // authenticate user , set up permissions
        $user = $this->createAuthUser('super_admin', array('ROLE_API_USER'));

        $aclManager = $this->getBBApp()->getContainer()->get('security.acl_manager');
        $aclManager->insertOrUpdateObjectAce(
            new ObjectIdentity($this->site->getObjectIdentifier(), get_class($this->site)),
            new UserSecurityIdentity($user->getGroups()[0]->getId(), 'BackBee\Security\Group'),
            MaskBuilder::MASK_VIEW
        );

        $aclManager->insertOrUpdateObjectAce(
            new ObjectIdentity('all', 'BackBee\Site\Layout'),
            new UserSecurityIdentity($user->getGroups()[0]->getId(), 'BackBee\Security\Group'),
            MaskBuilder::MASK_VIEW
        );

        $response = $this->getController()->getLayoutsAction($this->site->getUid());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $content);

        $this->assertCount(1, $content);

        $this->assertEquals($this->layout->getUid(), $content[0]['uid']);
    }

    /**
     * @covers ::getLayoutsAction
     */
    public function testGetLayoutsAction_noAuthorizedLayouts()
    {
        // authenticate a user with super admin authority
        $user = $this->createAuthUser('editor_layout', array('ROLE_API_USER'));

        // set up permissions
        $aclManager = $this->getBBApp()->getContainer()->get('security.acl_manager');
        $aclManager->insertOrUpdateObjectAce(
            new ObjectIdentity($this->site->getObjectIdentifier(), get_class($this->site)),
            new UserSecurityIdentity($user->getGroups()[0]->getId(), 'BackBee\Security\Group'),
            MaskBuilder::MASK_VIEW
        );

        $response = $this->getController()->getLayoutsAction($this->site->getUid());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $content);

        $this->assertCount(0, $content);
    }

    /**
     * @covers ::getLayoutsAction
     */
    public function test_getLayoutsAction_invalideSite()
    {
        // authenticate a user with super admin authority
        $this->createAuthUser('editor_layout', array('ROLE_API_USER'));

        $controller = $this->getController();
        $response = $controller->getLayoutsAction('siteThatDoesntExist', new Request());

        $this->assertEquals(404, $response->getStatusCode());
    }

        /**
     * @covers ::getLayoutsAction
     */
    public function test_getSiteController()
    {
        $user = $this->createAuthUser('super_admin', array('ROLE_API_USER'));

        $aclManager = $this->getBBApp()->getContainer()->get('security.acl_manager');
        $aclManager->insertOrUpdateObjectAce(
            new ObjectIdentity($this->site->getObjectIdentifier(), get_class($this->site)),
            new UserSecurityIdentity($user->getGroups()[0]->getId(), 'BackBee\Security\Group'),
            MaskBuilder::MASK_VIEW
        );
        // authenticate a user with super admin authority
        $controller = $this->getController();
        $response = $controller->getCollectionAction(new Request());

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $content);

        $this->assertCount(1, $content);
    }

    protected function tearDown()
    {
        $this->dropDb($this->getBBApp());
        $this->bbapp->stop();
    }
}
