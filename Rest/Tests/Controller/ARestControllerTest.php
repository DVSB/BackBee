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
use BackBuilder\Security\User;
use BackBuilder\Rest\Tests\Fixtures\Model\MockUser;

/**
 * Test for AclController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 * 
 * @coversDefaultClass \BackBuilder\Rest\Controller\ARestController
 */
class ARestControllerTest extends TestCase
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
   }
    
    protected function getController()
    {
        $controller = new \BackBuilder\Rest\Tests\Fixtures\Controller\MockARestImplController();
        $controller->setContainer($this->getBBApp()->getContainer());
        
        return $controller;
    }
    
    /**
     * @covers ::formatCollection
     */
    public function test_formatCollection()
    {
        $arrayCollection = [new MockUser()];
        
        $serialized = $this->getController()->formatCollection($arrayCollection);
        
        $this->assertEquals([[
            'id' => 1,
            'login' => 'userLogin'
        ]], json_decode($serialized, true));
    }
    
    /**
     * @covers ::formatItem
     */
    public function test_formatItem()
    {
        $serialized = $this->getController()->formatItem(new MockUser());

        $this->assertEquals([
            'id' => 1,
            'login' => 'userLogin'
        ], json_decode($serialized, true));
    }
    
    /**
     * @covers ::deserializeEntity
     */
    public function test_deserializeEntity()
    {
        $user = new User('userLogin', 'userPassword');
        
        $data = ['login' => 'userLoginChanged'];
        
        $serialized = $this->getController()->deserializeEntity($data, $user);
        
        $this->assertEquals($data['login'], $user->getLogin());
    }
    
    /**
     * @covers ::create404ResponseAction
     */
    public function test_create404Response()
    {
        $response = $this->getController()->create404ResponseAction();
        
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(404, $response->getStatusCode());
    }
    
    /**
     * @covers ::createValidationExceptionAction
     */
    public function test_create404createValidationExceptionAction()
    {
        $response = $this->getController()->createValidationExceptionAction();
        $this->assertInstanceOf('BackBuilder\Rest\Exception\ValidationException', $response);
    }
}
