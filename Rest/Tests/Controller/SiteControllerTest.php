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

use BackBuilder\Rest\Controller\SiteController;
use BackBuilder\Tests\TestCase;

use BackBuilder\Security\Token\BBUserToken,
    BackBuilder\Security\Acl\Loader\YmlLoader,
    BackBuilder\Security\Acl\Permission\MaskBuilder;

use BackBuilder\Site\Site,
    BackBuilder\Site\Layout;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity;


/**
 * Test for UserController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 * 
 * @coversDefaultClass \BackBuilder\Rest\Controller\SiteController
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
        $this->layout->setPath($this->bbapp->getBBDir() . '/Rest/Tests/Fixtures/Controller/defaultLayout.html.twig');
        $this->layout->setData(json_encode(array(
            'templateLayouts' => array(
                'title' => 'zone_1234567'
            )
        )));
        
        $this->site->addLayout($this->layout);
        $this->getEntityManager()->persist($this->layout);
        $this->getEntityManager()->persist($this->site);
        
        $this->getEntityManager()->flush();
        
        // load acl
        $loader = new YmlLoader();
        $loader->setContainer($this->getBBApp()->getContainer());
        $loader->load('groups:
  super_admin:
    sites:
      resources: all
      actions: all
    layouts:
      resources: all
      actions: all
  editor_layout:
    sites:
      resources: all
      actions: all
    layouts:
      resources: [layout1]
      actions: all
');
        
        
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
        $token = $this->createAuthUser('super_admin', array('ROLE_API_USER'));
        
        $aclManager = $this->getBBApp()->getContainer()->get('security.acl_manager');
        $aclManager->insertOrUpdateObjectAce(
            new ObjectIdentity($this->site->getObjectIdentifier(), get_class($this->site)), 
            new UserSecurityIdentity('super_admin', 'BackBuilder\Security\Group'), 
            MaskBuilder::MASK_VIEW
        );
        
        $aclManager->insertOrUpdateObjectAce(
            new ObjectIdentity('class', 'BackBuilder\Site\Layout'), 
            new UserSecurityIdentity('super_admin', 'BackBuilder\Security\Group'), 
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
        $token = $this->createAuthUser('editor_layout', array('ROLE_API_USER'));

        // set up permissions
        $aclManager = $this->getBBApp()->getContainer()->get('security.acl_manager');
        $aclManager->insertOrUpdateClassAce(new ObjectIdentity($this->site->getUid(), get_class($this->site)), UserSecurityIdentity::fromAccount($token->getUser()), MaskBuilder::MASK_VIEW);
        
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
    

    protected function tearDown()
    {
        $this->dropDb($this->getBBApp());
        $this->bbapp->stop();
    }

}