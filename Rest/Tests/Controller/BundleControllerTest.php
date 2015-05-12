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

use BackBee\Rest\Patcher\OperationBuilder;
use BackBee\Rest\Test\RestTestCase;
use BackBee\Security\Group;
use BackBee\Security\User;
use BackBee\Site\Site;

/**
 * Test for BundleController class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\Rest\Controller\BundleController
 * @group Rest
 */
class BundleControllerTest extends RestTestCase
{
    private $em;
    private $site;
    private $user;

    protected function setUp()
    {
        $this->initAutoload();
        $bbapp = $this->getBBApp();
        $bbapp->setIsStarted(true);
        $this->initDb($bbapp);
        $this->initAcl();

        $this->em = $this->getBBApp()->getEntityManager();
        $this->site = new Site();
        $this->site->setLabel('Test Site')->setServerName('test_server');

        $this->groupEditor = new Group();
        $this->groupEditor->setName('groupName');
        $this->groupEditor->setSite($this->site);

        $this->getBBApp()->getContainer()->set('site', $this->site);

        $this->em->persist($this->site);
        $this->em->persist($this->groupEditor);

        $this->em->flush();

        $this->user = $this->createAuthUser($this->groupEditor->getId());
        $this->em->persist($this->user);
        $this->em->flush();
    }

    public function testGetCollectionAction()
    {
        $response = $this->sendRequest(self::requestGet('/rest/1/bundle'));
        $this->assertTrue($response->isOk());
        $bundlesConfig = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $bundlesConfig);
        $this->assertCount(2, $bundlesConfig);

        foreach ($bundlesConfig as $bundleConfig) {
            $this->assertArrayHasKey('id', $bundleConfig);
            $this->assertArrayHasKey('name', $bundleConfig);
            $this->assertArrayHasKey('description', $bundleConfig);
            $this->assertArrayHasKey('enable', $bundleConfig);
            $this->assertArrayHasKey('config_per_site', $bundleConfig);
            $this->assertArrayHasKey('category', $bundleConfig);
            $this->assertArrayHasKey('thumbnail', $bundleConfig);
        }
    }

    public function testGetAction()
    {
        $response = $this->sendRequest(self::requestGet('/rest/1/bundle/demo'));
        $this->assertTrue($response->isOk());
        $bundleConfig = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $bundleConfig);
        $this->assertCount(10, $bundleConfig);
        $this->assertArrayHasKey('id', $bundleConfig);
        $this->assertArrayHasKey('name', $bundleConfig);
        $this->assertArrayHasKey('description', $bundleConfig);
        $this->assertArrayHasKey('enable', $bundleConfig);
        $this->assertArrayHasKey('config_per_site', $bundleConfig);
        $this->assertArrayHasKey('category', $bundleConfig);
        $this->assertArrayHasKey('thumbnail', $bundleConfig);
    }

    public function testGetActionNotFound()
    {
        $response = $this->sendRequest(self::requestGet('/rest/1/bundle/bundle-dont-exists'));
        $this->assertTrue($response->isNotFound());
        $content = json_decode($response->getContent(), true);
        $this->assertNull($content);
    }

    public function testPatchAction()
    {
        $response = $this->sendRequest(self::requestPatch('/rest/1/bundle/demo', (new OperationBuilder())
            ->replace('enable', false)
            ->replace('category', 'New category')
            ->getOperations()
        ));
        $this->assertTrue($response->isEmpty());

        /* let's check patch actions have been applied */
        $demoBundle = $this->getBBApp()->getBundle('demo');
        $this->assertFalse($demoBundle->isEnabled());
        $this->assertEquals('New category', $demoBundle->getProperty('category')[0]);
    }

    public function testPatchActionNotFound()
    {
        $response = $this->sendRequest(self::requestPatch('/rest/1/bundle/bundle-dont-exists', (new OperationBuilder())
            ->replace('name', 'New DemoBundle name')
            ->replace('description', 'This is a new description')
            ->getOperations()
        ));
        $this->assertTrue($response->isNotFound());
    }

    protected function tearDown()
    {
        $this->dropDb($this->getBBApp());
        $this->getBBApp()->stop();
    }
}
