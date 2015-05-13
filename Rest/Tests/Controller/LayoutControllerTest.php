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
use BackBee\Security\Group;
use BackBee\Security\User;
use BackBee\Site\Layout;
use BackBee\Site\Site;
use BackBee\Workflow\State;

/**
 * Test for LayoutController class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 * @author      Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\Rest\Controller\LayoutController
 * @group Rest
 */
class LayoutControllerTest extends RestTestCase
{
    protected $bbapp;

    protected function setUp()
    {
        $this->initAutoload();

        $this->bbapp = $this->getBBApp();
        $this->em = $this->getEntityManager();
        $this->initDb($this->bbapp);
        $this->initAcl();
        $this->getBBApp()->setIsStarted(true);

        // create site
        $this->site = (new Site())
            ->setLabel('Default Site')
        ;
        $this->em->persist($this->site);

        // create site layout
        $this->siteLayout = (new Layout('site1'))
            ->setLabel('Site layout')
            ->setData("{}")
            ->setSite($this->site)
            ->setPath($this->getBBApp()->getBaseRepository().'/Layouts')
        ;
        $this->em->persist($this->siteLayout);

        $state = (new State())
            ->setCode(2)
            ->setLabel('Layout 1 state')
            ->setLayout($this->siteLayout)
            ->setListener('stdClass')
        ;

        $this->em->persist($state);
        $this->siteLayout->addState($state);

        // create global layout
        $this->globalLayout = (new Layout('global1'))
            ->setLabel('Global layout')
            ->setData("{}")
            ->setPath($this->getBBApp()->getBaseRepository().'/Layouts')
        ;
        $this->em->persist($this->globalLayout);

        $this->groupEditor = new Group();
        $this->groupEditor->setName('groupName');
        $this->groupEditor->setSite($this->site);

        $this->em->persist($this->groupEditor);
        $this->getContainer()->set('site', $this->site);

        $this->em->flush();

        // permissions
        $this->user = $this->createAuthUser($this->groupEditor->getId());
        $this->em->persist($this->user);
        $this->em->flush();
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

    public function testGetWorkflowStateAction()
    {
        $url = '/rest/1/layout/'. $this->siteLayout->getUid().'/workflow_state';
        $response = $this->sendRequest(self::requestGet($url));
        $this->assertTrue($response->isOk(), sprintf('HTTP 200 expected, HTTP %s returned.', $response->getStatusCode()));
        $workflowStates = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $workflowStates);
    }

    public function testGetAction()
    {
        $url = '/rest/1/layout/'. $this->siteLayout->getUid();

        $response = $this->sendRequest(self::requestGet($url));
        $this->assertTrue($response->isOk(), sprintf('HTTP 200 expected, HTTP %s returned.', $response->getStatusCode()));
        $layout = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $layout);

        $properties = ['site_uid', 'site_label', 'data', 'workflow_states', 'uid', 'label', 'path', 'created', 'modified', 'picpath'];

        foreach ($properties as $property) {
            $this->assertArrayHasKey($property, $layout);
        }

        $this->assertEquals($this->siteLayout->getUid(), $layout['uid']);
    }

    public function testGetCollectionAction()
    {
        $url = '/rest/1/layout';

        $response = $this->sendRequest(self::requestGet($url));
        $content = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $content);

        $this->assertCount(1, $content);
        $layoutTest = $content[0];

        $this->assertArrayHasKey('workflow_states', $layoutTest);
        $this->assertCount(2, $layoutTest['workflow_states']);
    }

    public function testGetCollectionActionWithSite()
    {
        $url = '/rest/1/layout';

        $response = $this->sendRequest(self::requestGet($url, ['site_uid' => $this->site->getUid()]));
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
