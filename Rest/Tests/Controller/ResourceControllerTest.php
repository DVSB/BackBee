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

use BackBee\Rest\Test\RestTestCase;
use BackBee\Security\Group;
use BackBee\Security\User;
use BackBee\Site\Site;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for ResourceController class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\Rest\Controller\ResourceController
 * @group Rest
 */
class ResourceControllerTest extends RestTestCase
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

    public function testUploadAction()
    {
        $url = '/rest/1/resource/upload';

        $imageName = 'BackBee.png'; // can't be changed, real file
        $image = new UploadedFile(
                $this->getBBApp()->getResourcesRepository().DIRECTORY_SEPARATOR.$imageName,
                $imageName,
                'image/png',
                1300,
                null,
                true
            );

        $request = new Request([], [], [], [], ['file' => $image], [
            'REQUEST_URI'    => $url,
            'CONTENT_TYPE'   => 'image/png',
            'REQUEST_METHOD' => 'POST',
        ]);

        $response = $this->sendRequest($request);
        $this->assertEquals(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('HTTP %s expected, HTTP %s returned.', Response::HTTP_CREATED, $response->getStatusCode())
        );

        $fileProperties = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $fileProperties);

        $properties = ['originalname', 'path', 'filename'];

        foreach ($properties as $property) {
            $this->assertArrayHasKey($property, $fileProperties);
        }

        $this->assertEquals($imageName, $fileProperties['originalname']);
    }

    public function testUploadActionFileTooBigFails()
    {
        $url = '/rest/1/resource/upload';

        $imageName = 'BackBee.png'; // can't be changed, real file
        $image = new UploadedFile(
                $this->getBBApp()->getResourcesRepository().DIRECTORY_SEPARATOR.$imageName,
                $imageName,
                'image/png',
                10000000000, // very big file
                null,
                true
            );

        $request = new Request([], [], [], [], ['file' => $image], [
            'REQUEST_URI'    => $url,
            'CONTENT_TYPE'   => 'image/png',
            'REQUEST_METHOD' => 'POST',
        ]);

        $response = $this->sendRequest($request);
        $this->assertTrue($response->isClientError(), sprintf('HTTP %s expected, HTTP %s returned.', Response::HTTP_UNAUTHORIZED, $response->getStatusCode()));
        $this->assertEquals(['exception' => 'Too big file, the max file size is 2097152'], json_decode($response->getContent(), true));
    }

    protected function tearDown()
    {
        $this->dropDb($this->getBBApp());
        $this->getBBApp()->stop();
    }
}
