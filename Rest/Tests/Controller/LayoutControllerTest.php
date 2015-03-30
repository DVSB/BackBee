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
use BackBee\Rest\Controller\LayoutController;
use BackBee\Rest\Test\RestTestCase;
use BackBee\Security\User;
use BackBee\Site\Layout;
use BackBee\Site\Site;
use BackBee\Workflow\State;

/**
 * Test for SecurityController class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\Controller\LayoutController
 */
class LayoutControllerTest extends RestTestCase
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
        $user->setEmail('user123@provider.com');
        $user->setPassword(md5('password123'));
        $user->setActivated(true);
        $this->getEntityManager()->persist($user);

        // create site
        $this->site = (new Site())
            ->setLabel('Default Site')
        ;
        $this->getEntityManager()->persist($this->site);

        // create site layout
        $this->siteLayout = (new Layout('site1'))
            ->setLabel('Site layout')
            ->setData("{}")
            ->setSite($this->site)
            ->setPath($this->getBBApp()->getBaseRepository().'/Layouts')
        ;
        $this->getEntityManager()->persist($this->siteLayout);

        $state = (new State())
            ->setCode(2)
            ->setLabel('Layout 1 state')
            ->setLayout($this->siteLayout)
            ->setListener('stdClass')
        ;

        $this->getEntityManager()->persist($state);
        $this->siteLayout->addState($state);

        // create global layout
        $this->globalLayout = (new Layout('global1'))
            ->setLabel('Global layout')
            ->setData("{}")
            ->setPath($this->getBBApp()->getBaseRepository().'/Layouts')
        ;
        $this->getEntityManager()->persist($this->globalLayout);

        $this->getEntityManager()->flush();
    }

    /**
     * @return \BackBee\Security\Tests\Controller\LayoutController
     */
    protected function getController()
    {
        $controller = new LayoutController();
        $controller->setContainer($this->bbapp->getContainer());

        return $controller;
    }

    /**
     * @covers ::getCollectionAction
     */
    public function test_getCollectionAction_global()
    {
        $request = new Request();

        $response = $this->getController()->getCollectionAction($request);
        $content = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $content);

        $this->assertCount(1, $content);
        $layoutTest = $content[0];

        $this->assertArrayHasKey('workflow_states', $layoutTest);
        $this->assertCount(2, $layoutTest['workflow_states']);
    }

    /**
     * @covers ::getCollectionAction
     */
    public function test_getCollectionAction_site()
    {
        $request = new Request([], [], ['site' => $this->site]);
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
