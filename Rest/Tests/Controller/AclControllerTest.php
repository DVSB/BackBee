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

use BackBuilder\Rest\Controller\AclController;
use BackBuilder\Tests\TestCase;


use BackBuilder\Security\Group,
    BackBuilder\Site\Site;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use BackBuilder\Security\Acl\Permission\MaskBuilder;

/**
 * Test for AclController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 * 
 * @coversDefaultClass \BackBuilder\Rest\Controller\AclController
 */
class AclControllerTest extends TestCase
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
        $this->initAcl();
        
        
        $this->site = new Site();
        $this->site->setLabel('Test Site')->setServerName('test_server');
        
        $this->groupEditor = new Group();
        $this->groupEditor->setName('groupName');
        $this->groupEditor->setSite($this->site);
        $this->groupEditor->setIdentifier('GROUP_ID');
        
        $bbapp->getEntityManager()->persist($this->site);
        $bbapp->getEntityManager()->persist($this->groupEditor);
        
        $bbapp->getEntityManager()->flush();
        
        // setup ACE for site
        $aclProvider = $this->getSecurityContext()->getACLProvider();
        $objectIdentity = ObjectIdentity::fromDomainObject($this->site);
        $acl = $aclProvider->createAcl($objectIdentity);
        
         // retrieving the security identity of the currently logged-in user
        $securityIdentity = new UserSecurityIdentity($this->groupEditor->getName(), 'BackBuilder\Security\Group');

        // grant owner access
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_EDIT);
        
        $aclProvider->updateAcl($acl);
    }
    
    protected function getController()
    {
        $controller = new AclController();
        $controller->setContainer($this->getBBApp()->getContainer());
        
        return $controller;
    }
    
    
    /**
     * @covers ::getClassCollectionAction
     */
    public function test_getClassCollectionAction()
    {
        $response = $this->getBBApp()->getController()->handle(Request::create('/rest/1/acl/class/'));
        
        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $res);
    }
    
    /**
     * @covers ::getMaskCollectionAction
     */
    public function test_getMaskCollectionAction()
    {
        $response = $this->getBBApp()->getController()->handle(Request::create('/rest/1/acl/permissions/'));
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertCount(11, $res);
        
        $this->assertArrayHasKey('view', $res);
        $this->assertEquals(1, $res['view']);
        
        $this->assertInternalType('array', $res);
    }

    /**
     * @covers ::postPermissionMapAction
     */
    public function test_postPermissionMapAction_missingFields() 
    {
        $data = [[
            'object_id' => $this->site->getObjectIdentifier(),
            'object_class' => get_class($this->site),
            'permissions' => ['view' => 1]
        ]];
        
        $response = $this->getBBApp()->getController()->handle(new Request([], $data, [
            '_action' => 'postPermissionMapAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/test/', 'REQUEST_METHOD' => 'POST'] ));
        
        $this->assertEquals(400, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('errors', $res);
        $this->assertArrayHasKey(0, $res['errors']);
        $this->assertArrayHasKey('sid', $res['errors'][0]);
        
        
        $data = [[
            'sid' => $this->groupEditor->getId(),
            'permissions' => ['view' => 1]
        ]];
        
        $response = $this->getBBApp()->getController()->handle(new Request([], $data, [
            '_action' => 'postPermissionMapAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/test/', 'REQUEST_METHOD' => 'POST'] ));
        
        $this->assertEquals(400, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $res);
        $this->assertArrayHasKey(0, $res['errors']);
        $this->assertArrayHasKey('object_class', $res['errors'][0]);
    }
    
    /**
     * @covers ::postPermissionMapAction
     */
    public function test_postPermissionMapAction() 
    {
        $data = [
            [
                // object scope
                'sid' => $this->groupEditor->getId(),
                'object_id' => $this->site->getObjectIdentifier(),
                'object_class' => get_class($this->site),
                'permissions' => [
                    'view' => 1,
                    'create' => 1,
                    'edit' => 1,
                    'delete' => 1,
                    'undelete' => 'off',
                    'commit' => '0',
                    'publish' => 1,
                    'operator' => 1,
                    'master' => 'false',
                    'owner' => 1
                ]
            ], 
            [
                // class scope
                'sid' => $this->groupEditor->getId(),
                'object_class' => 'BackBuilder\Site\Layout',
                'permissions' => [
                    'view' => 'true',
                    'create' => '1',
                    'edit' => 1,
                    'commit' => '0',
                    'publish' => 'off'
                ]
            ]
        ];
        
        $response = $this->getBBApp()->getController()->handle(new Request([], $data, [
            '_action' => 'postPermissionMapAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/acl/', 'REQUEST_METHOD' => 'POST'] ));
        
        $this->assertEquals(204, $response->getStatusCode());
        
        $securityIdentity = new UserSecurityIdentity($this->groupEditor->getId(), 'BackBuilder\Security\Group');

        $aclManager = $this->getBBApp()->getContainer()->get("security.acl_manager");
        
        $objectIdentity = new ObjectIdentity($this->site->getObjectIdentifier(), get_class($this->site));
        $ace = $aclManager->getObjectAce($objectIdentity, $securityIdentity);
        $this->assertInstanceOf('Symfony\Component\Security\Acl\Domain\Entry', $ace);
        $this->assertEquals(687, $ace->getMask());
        
        $objectIdentity = new ObjectIdentity('class', 'BackBuilder\Site\Layout');
        $ace = $aclManager->getClassAce($objectIdentity, $securityIdentity);
        $this->assertInstanceOf('Symfony\Component\Security\Acl\Domain\Entry', $ace);
        $this->assertEquals(7, $ace->getMask());
    }

    
    
    /**
     * @covers ::postPermissionMapAction
     */
    public function test_postPermissionMapAction_invalidPermission() 
    {
        $data = [[
            'sid' => $this->groupEditor->getId(),
            'object_class' => get_class($this->site),
            'permissions' => ['permissionThatDoesnExist' => 1]
        ]];
        
        $response = $this->getBBApp()->getController()->handle(new Request([], $data, [
            '_action' => 'postPermissionMapAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/acl/', 'REQUEST_METHOD' => 'POST'] ));
        
        $res = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('errors', $res);

        $this->assertEquals('Invalid permission mask: permissionThatDoesnExist', $res['errors'][0]['permissions'][0]);
    }
    
    
    /**
     * @covers ::deleteObjectAceAction
     */
    public function test_deleteObjectAceAction()
    {
        // save the ACE
        $objectIdentity = new ObjectIdentity($this->site->getObjectIdentifier(), get_class($this->site));
        $securityIdentity = new UserSecurityIdentity($this->groupEditor->getId(), 'BackBuilder\Security\Group');
        $aclManager = $this->getBBApp()->getContainer()->get("security.acl_manager");
        $aclManager->insertOrUpdateClassAce($objectIdentity, $securityIdentity, MaskBuilder::MASK_VIEW);
        
        // valid request
        $data = [
            'object_class' => get_class($this->site),
            'object_id' => $this->site->getUid()
        ];

        $response = $this->getBBApp()->getController()->handle(Request::create(
            sprintf('/rest/1/acl/ace/object/%s/', $this->groupEditor->getId()),
            'DELETE', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data)
        ));

        $this->assertEquals(204, $response->getStatusCode());
        
        
        // invalid requests
        $data = [
            'object_class' => 'Class\That\Doenst\Exist',
            'object_id' => 'invalidObjectId_1234567890'
        ];
        $response = $this->getBBApp()->getController()->handle(Request::create(
            sprintf('/rest/1/acl/ace/object/%s/', $this->groupEditor->getId()),
            'DELETE', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data)
        ));

        $this->assertEquals(400, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('errors', $res);
        $this->assertArrayHasKey('object', $res['errors']);
    }
     
    /**
     * @covers ::deleteClassAceAction
     */
    public function test_deleteClassAceAction()
    {
        // save the ACE
        $objectIdentity = new ObjectIdentity('class', get_class($this->site));
        $securityIdentity = new UserSecurityIdentity($this->groupEditor->getId(), 'BackBuilder\Security\Group');
        $aclManager = $this->getBBApp()->getContainer()->get("security.acl_manager");
        $aclManager->insertOrUpdateClassAce($objectIdentity, $securityIdentity, MaskBuilder::MASK_VIEW);
        
        $data = [
            'object_class' => get_class($this->site)
        ];
        $response = $this->getBBApp()->getController()->handle(Request::create(
            sprintf('/rest/1/acl/ace/class/%s/', $this->groupEditor->getId()),
            'DELETE', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data)
        ));
        $this->assertEquals(204, $response->getStatusCode());
        
        // invalid requests
        $data = [
            'object_class' => 'Class\That\Doenst\Exist',
        ];
        $response = $this->getBBApp()->getController()->handle(Request::create(
            sprintf('/rest/1/acl/ace/class/%s/', $this->groupEditor->getId()),
            'DELETE', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data)
        ));

        $this->assertEquals(400, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('errors', $res);
        $this->assertArrayHasKey('object_class', $res['errors']);
    }
    
    /**
     * @covers ::postObjectAceAction
     */
    public function test_postObjectAceAction()
    {
        $data = [
            'group_id' => $this->groupEditor->getName(),
            'object_class' => get_class($this->site),
            'object_id' => $this->site->getUid(),
            'mask' => MaskBuilder::MASK_VIEW
        ];
        $response = $this->getBBApp()->getController()->handle(new Request([], $data, [
            '_action' => 'postObjectAceAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/test/', 'REQUEST_METHOD' => 'POST'] ));
        
        $res = json_decode($response->getContent(), true);

        $this->assertEquals(201, $response->getStatusCode());
        
        $this->assertInternalType('array', $res);
        $this->assertInternalType('int', $res['id']);
        
        $this->assertEquals($data['group_id'], $res['group_id']);
        $this->assertEquals($data['object_class'], $res['object_class']);
        $this->assertEquals($data['object_id'], $res['object_id']);
        $this->assertEquals($data['mask'], $res['mask']);
    }
    
    /**
     * @covers ::postObjectAceAction
     */
    public function test_postObjectAceAction_missingFields()
    {
         $response = $this->getBBApp()->getController()->handle(new Request([], [], [
            '_action' => 'postObjectAceAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/test/', 'REQUEST_METHOD' => 'POST'] ));
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertEquals(400, $response->getStatusCode());

        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('errors', $res);
        
        $this->assertArrayHasKey('group_id', $res['errors']);
        $this->assertArrayHasKey('object_class', $res['errors']);
        $this->assertArrayHasKey('object_id', $res['errors']);
        $this->assertArrayHasKey('mask', $res['errors']);
    }
    
    
    /**
     * @covers ::postClassAceAction
     */
    public function test_postClassAceAction()
    {
        $data = [
            'group_id' => $this->groupEditor->getName(),
            'object_class' => get_class($this->site),
            'mask' => MaskBuilder::MASK_VIEW
        ];
        $response = $this->getBBApp()->getController()->handle(new Request([], $data, [
            '_action' => 'postClassAceAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/test/', 'REQUEST_METHOD' => 'POST'] ));
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertEquals(201, $response->getStatusCode());
        
        $this->assertInternalType('array', $res);
        $this->assertInternalType('int', $res['id']);
        
        $this->assertEquals($data['group_id'], $res['group_id']);
        $this->assertEquals($data['object_class'], $res['object_class']);
        $this->assertEquals($data['mask'], $res['mask']);
    }
    
    
    
    /**
     * @covers ::postClassAceAction
     */
    public function test_postClassAceAction_missingFields()
    {
        $response = $this->getBBApp()->getController()->handle(new Request([], [], [
            '_action' => 'postClassAceAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/test/', 'REQUEST_METHOD' => 'POST'] ));
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertEquals(400, $response->getStatusCode());

        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('errors', $res);
        
        $this->assertArrayHasKey('group_id', $res['errors']);
        $this->assertArrayHasKey('object_class', $res['errors']);
        $this->assertArrayHasKey('mask', $res['errors']);
        
    }
    
    /**
     * @covers ::getClassCollectionAction
     */
    public function testGetClassCollectionAction()
    {
        $response = $this->getController()->getClassCollectionAction(new Request());
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertEquals('BackBuilder\Site\Site', $res[0]['class_type']);
    }

}