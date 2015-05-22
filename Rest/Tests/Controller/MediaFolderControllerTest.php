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

use BackBee\NestedNode\MediaFolder;
use BackBee\Rest\Patcher\OperationBuilder;
use BackBee\Rest\Test\RestTestCase;
use BackBee\Security\Group;
use BackBee\Security\User;
use BackBee\Site\Site;

use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for MediaFolderController class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\Rest\Controller\MediaFolderController
 * @group Rest
 */
class MediaFolderControllerTest extends RestTestCase
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

        /* Media folders creation */
        $this->mediaFolder = $this->createMediaFolder('12345678910', 'Media Folder 1', '/media/folder/1/');

        $this->subMediaFolder = $this->createMediaFolder('sub12345678910', 'SubMedia Folder', '/media/folder/1/submedia/folder');
        $this->subMediaFolder->setParent($this->mediaFolder);

        $this->subSubMediaFolder = $this->createMediaFolder('subsub12345678910', 'SubSubMedia Folder', '/media/folder/1/sub/submedia/folder');
        $this->subSubMediaFolder->setParent($this->subMediaFolder);

        $this->user = $this->createAuthUser($this->groupEditor->getId());
        $this->em->persist($this->user);
        $this->em->flush();
    }

    public function testGetCollectionAction()
    {
        $url = '/rest/1/media-folder';
        $response = $this->sendRequest(self::requestGet($url));

        $this->assertTrue($response->isOk(), sprintf('HTTP 200 expected, HTTP %s returned.', $response->getStatusCode()));
        $mediaFoldersCollection = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $mediaFoldersCollection);
        $this->assertCount(1, $mediaFoldersCollection);

        $properties = $this->expectedSingleProperties();
        foreach ($mediaFoldersCollection as $mediaFolder) {
            foreach ($properties as $property) {
                $this->assertArrayHasKey($property, $mediaFolder);
            }

            $this->assertEquals('12345678910', $mediaFolder['uid']);
        }
    }

    public function testGetCollectionActionWithParentUid()
    {
        $url = '/rest/1/media-folder';
        $response = $this->sendRequest(self::requestGet($url, ['parent_uid' => $this->subMediaFolder->getUid()]));

        $this->assertTrue($response->isOk(), sprintf('HTTP 200 expected, HTTP %s returned.', $response->getStatusCode()));
        $mediaFoldersCollection = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $mediaFoldersCollection);
        $this->assertCount(1, $mediaFoldersCollection);

        $properties = $this->expectedSingleProperties();
        foreach ($mediaFoldersCollection as $mediaFolder) {
            foreach ($properties as $property) {
                $this->assertArrayHasKey($property, $mediaFolder);
            }

            $this->assertEquals('subsub12345678910', $mediaFolder['uid']);
        }
    }

    public function testGetAction()
    {
        $url = '/rest/1/media-folder/'.$this->mediaFolder->getUid();

        $response = $this->sendRequest(self::requestGet($url));

        $this->assertTrue($response->isOk(), sprintf('HTTP 200 expected, HTTP %s returned.', $response->getStatusCode()));
        $mediaFolder = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $mediaFolder);

        $properties = $this->expectedSingleProperties();
        foreach ($properties as $property) {
            $this->assertArrayHasKey($property, $mediaFolder);
        }

        $this->assertEquals($this->mediaFolder->getUid(), $mediaFolder['uid']);
        $this->assertEquals($this->mediaFolder->getTitle(), $mediaFolder['title']);
        $this->assertEquals($this->mediaFolder->getUrl(), $mediaFolder['url']);
    }

    public function testPatchAction()
    {
        $url = '/rest/1/media-folder/'.$this->mediaFolder->getUid();
        $updatedTitle = 'Media Folder 1 updated';

        $response = $this->sendRequest(self::requestPatch($url, (new OperationBuilder())
            ->replace('title', $updatedTitle)
            ->getOperations()
        ));

        $this->assertTrue($response->isEmpty(), sprintf('HTTP % expected, HTTP %s returned.', Response::HTTP_NO_CONTENT, $response->getStatusCode()));
        $this->assertEmpty(json_decode($response->getContent(), true));

        $mediaFolder = $this->em->getRepository('\BackBee\NestedNode\MediaFolder')->find($this->mediaFolder->getUid());

        $this->assertEquals($updatedTitle, $mediaFolder->getTitle());
    }

    public function testPutAction()
    {
        $url = '/rest/1/media-folder/'.$this->mediaFolder->getUid();
        $updatedTitle = 'Media Folder 1 updated';

        $response = $this->sendRequest(self::requestPut($url,['title' => $updatedTitle]));

        $this->assertTrue($response->isEmpty(), sprintf('HTTP % expected, HTTP %s returned.', Response::HTTP_NO_CONTENT, $response->getStatusCode()));
        $this->assertEmpty(json_decode($response->getContent(), true));

        $mediaFolder = $this->em->getRepository('\BackBee\NestedNode\MediaFolder')->find($this->mediaFolder->getUid());

        $this->assertEquals($updatedTitle, $mediaFolder->getTitle());
    }

    public function testPostAction()
    {
        $url = '/rest/1/media-folder';
        $title = 'Media Folder creation';
        $urlMedia = '/an/url/';

        $response = $this->sendRequest(self::requestPost($url,['title' => $title, 'url' => $urlMedia]));

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode(), sprintf('HTTP % expected, HTTP %s returned.', Response::HTTP_CREATED, $response->getStatusCode()));
        $this->assertTrue($response->headers->has('bb-resource-uid'));

        $uid = $response->headers->get('bb-resource-uid');

        $mediaFolder = $this->em->getRepository('\BackBee\NestedNode\MediaFolder')->find($uid);

        $this->assertEquals($title, $mediaFolder->getTitle());
        $this->assertEquals($urlMedia, $mediaFolder->getUrl());
    }

    public function testDeleteAction()
    {
        $url = '/rest/1/media-folder/'.$this->subMediaFolder->getUid();

        $response = $this->sendRequest(self::requestDelete($url));

        $this->assertTrue($response->isEmpty(), sprintf('HTTP % expected, HTTP %s returned.', Response::HTTP_NO_CONTENT, $response->getStatusCode()));
        $this->assertEmpty(json_decode($response->getContent(), true));

        $this->em->refresh($this->subMediaFolder->getRoot());
        $this->em->clear(); /* mandatory because this is queryBuilder and not the entityManager which delete the media folder */
        $mediaFolder = $this->em->getRepository('\BackBee\NestedNode\MediaFolder')->find($this->subMediaFolder->getUid());

        $this->assertNull($mediaFolder, 'Media Folder deletion fails.');
    }

    public function testDeleteActionWithRootMediaFolderFails()
    {
        $url = '/rest/1/media-folder/'.$this->mediaFolder->getUid();

        $response = $this->sendRequest(self::requestDelete($url));

        $exception = json_decode($response->getContent(), true);
        $this->assertTrue($response->isClientError(), sprintf('HTTP % expected, HTTP %s returned.', Response::HTTP_BAD_REQUEST, $response->getStatusCode()));
        $this->assertEquals('Cannot remove the root node of the MediaFolder.', $exception['exception']);
    }

    private function createMediaFolder($folderName, $folderTitle, $folderUrl)
    {
        $mediaFolder = new MediaFolder($folderName, $folderTitle, $folderUrl);
        $this->em->persist($mediaFolder);
        $this->em->flush($mediaFolder);

        return $mediaFolder;
    }

    private function expectedSingleProperties()
    {
        return [
            'uid',
            'is_root',
            'root_uid',
            'parent_uid',
            'title',
            'url',
            'has_children',
            'modified',
            'created',
        ];
    }

    protected function tearDown()
    {
        $this->dropDb($this->getBBApp());
        $this->getBBApp()->stop();
    }
}
