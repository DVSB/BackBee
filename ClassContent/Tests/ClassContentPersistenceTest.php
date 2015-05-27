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

namespace BackBee\ClassContent\Test;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Element\Text;
use BackBee\ClassContent\Tests\Mock\MockContent;
use BackBee\Tests\BackBeeTestCase;

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class ClassContentPersistenceTest extends BackBeeTestCase
{
    /**
     * @var BackBee\ClassContent\ClassContentManager
     */
    private static $contentManager;

    public static function setUpBeforeClass()
    {
        self::$kernel->resetDatabase();

        $user = self::$kernel->createAuthenticatedUser('content_persistence_test');

        self::$em->persist($user);
        self::$em->flush($user);

        self::$contentManager = self::$app->getContainer()->get('classcontent.manager');
        self::$contentManager->setBBUserToken(self::$app->getSecurityContext()->getToken());

    }

    public function setUp()
    {
        self::$em->clear();

        self::$kernel->resetDatabase([
            self::$em->getClassMetadata('BackBee\ClassContent\AbstractClassContent'),
            self::$em->getClassMetadata('BackBee\ClassContent\Revision'),
        ]);
    }

    public function testContentDraftOnFlush()
    {
        $content = new MockContent();

        $this->assertNull($content->getDraft());
        $this->assertNull(self::$contentManager->getDraft($content));

        self::$em->persist($content);
        self::$em->flush($content);

        $this->assertNotNull($content->getDraft());
        $this->assertNotNull(self::$contentManager->getDraft($content));

        // Should create draft for content's elements that is instanceof BackBee\ClassContent\AbstractClassContent
        foreach ($content->getData() as $element) {
            if ($element instanceof AbstractClassContent) {
                $this->assertNotNull(self::$contentManager->getDraft($element));
            }
        }
    }

    public function testContentDraftOnFlushWithoutBBUserToken()
    {
        $token = self::$app->getSecurityContext()->getToken();
        self::$app->getSecurityContext()->setToken(null);
        self::$contentManager->setBBUserToken(null);

        $text = new Text();

        $this->assertNull($text->getDraft());

        self::$em->persist($text);
        self::$em->flush($text);

        $this->assertNull($text->getDraft());

        self::$app->getSecurityContext()->setToken($token);
        self::$contentManager->setBBUserToken($token);
    }

    public function testDeleteContent()
    {
        $content = new MockContent();

        self::$em->persist($content);
        self::$em->flush($content);

        self::$em->clear();

        $uid = $content->getUid();
        $repository = self::$em->getRepository(get_class($content));

        $this->assertNotNull($content = $repository->find($uid));

        $repository->deleteContent($content);
        self::$em->flush();

        $this->assertNull($repository->find($uid));
    }

    public function testAutomaticReplacementOnDeleteClassContentElement()
    {
        $content = new MockContent();

        self::$em->persist($content);
        self::$em->flush($content);

        self::$em->clear();

        $repository = self::$em->getRepository(get_class($content));
        $content = $repository->find($content->getUid());
        $titleUid = $content->title->getUid();

        $repository->deleteContent($content->title, true);
        self::$em->flush();

        $this->assertNotNull($content->title);
        $this->assertTrue($titleUid !== $content->title->getUid());
        $this->assertNotNull(
            self::$em->find(get_class($content->title), $content->title->getUid()),
            'Replacement title must be persisted into database.'
        );
        $this->assertNotNull(
            self::$contentManager->getDraft($content->title),
            'Replacement title must also own a draft'
        );
    }
}
