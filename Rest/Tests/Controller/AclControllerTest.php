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
        $siteIdentity = ObjectIdentity::fromDomainObject($this->site);
        $acl = $aclProvider->createAcl($siteIdentity);
        
         // retrieving the security identity of the currently logged-in user
        $securityIdentity = new UserSecurityIdentity($this->groupEditor->getName(), 'BackBuilder\Security\Group');

        // grant owner access
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_PUBLISH);
        
        $aclProvider->updateAcl($acl);
    }
    
    protected function getController()
    {
        $controller = new AclController();
        $controller->setContainer($this->getBBApp()->getContainer());
        
        return $controller;
    }

    
    /**
     * @covers ::getEntryCollectionAction
     */
    public function testGetClassCollectionAction()
    {
        $response = $this->getBBApp()->getController()->handle(new Request(array(), array(
        ), array(
            '_action' => 'getClassCollectionAction',
            '_controller' =>  $this->getController()
        ), array(), array(), array('REQUEST_URI' => '/rest/1/test/') ));
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertEquals('BackBuilder\Site\Site', $res[0]['class_type']);
    }
    
}