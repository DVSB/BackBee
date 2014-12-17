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
use Symfony\Component\HttpFoundation\Response;
use BackBuilder\Rest\Controller\LayoutController;
use BackBuilder\Tests\TestCase;
use BackBuilder\Security\User;
use BackBuilder\Security\Token\BBUserToken;
use BackBuilder\Site\Layout;
use BackBuilder\Workflow\State;

/**
 * Test for SecurityController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBuilder\Rest\Controller\LayoutController
 */
class LayoutControllerTest extends TestCase
{
    protected $bbapp;

    protected function setUp()
    {
        $this->initAutoload();

        $this->bbapp = $this->getBBApp();
        $this->initDb($this->bbapp);

        $this->bbapp->start();

        // valid user
        $user = new User();
        $user->setLogin('user123');
        $user->setPassword(md5('password123'));
        $user->setActivated(true);
        $this->getEntityManager()->persist($user);
        
        // create layout
        $layout = (new Layout('test1'))
            ->setLabel('Test layout')
            ->setData("{}")
            ->setPath($this->getBBApp()->getBaseRepository())
        ;
        $this->getEntityManager()->persist($layout);
        
        $state = (new State())
            ->setCode(2)
            ->setLabel('Layout 1 state')
            ->setLayout($layout)
            ->setListener('stdClass')
        ;
        
        $this->getEntityManager()->persist($state);
        $layout->addState($state);
        
        $this->getEntityManager()->flush();
    }

    /**
     *
     * @return \BackBuilder\Security\Tests\Controller\LayoutController
     */
    protected function getController()
    {
        $controller = new LayoutController();
        $controller->setContainer($this->bbapp->getContainer());

        return $controller;
    }

    /**
     * @covers ::getCollectionAction
     *
     */
    public function test_getCollectionAction()
    {
        $request = new Request();
        $response = $this->getController()->getCollectionAction($request);
        $content = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $content);
        $this->assertCount(1, $content);
        $layoutTest = $content[0];

        $this->assertArrayHasKey('workflow_states', $layoutTest);
        $this->assertCount(3, $layoutTest['workflow_states']);
        
    }

    protected function tearDown()
    {
        $this->dropDb($this->getBBApp());
        $this->getBBApp()->stop();
    }
}
