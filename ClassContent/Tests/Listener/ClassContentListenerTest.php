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

namespace BackBee\ClassContent\Tests\Listener;

use BackBee\ClassContent\AbstractContent;
use BackBee\ClassContent\Listener\ClassContentListener;
use BackBee\ClassContent\Tests\Mock\MockContent;
use BackBee\Controller\Event\PostResponseEvent;
use BackBee\Tests\BackBeeTestCase;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Classcontent Listener class.
 *
 * @category  BackBee
 *
 * @author    f.kroockmann <florian.kroockmann@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\ClassContent\Tests\Listener\ClassContentListener
 * @group Rest
 */
class ClassContentListenerTest extends BackBeeTestCase
{
    const POST_CALL_EVENT_NAME = 'rest.controller.classcontentcontroller.getAction.postcall';

    /**
     * @var BackBee\ClassContent\ClassContentManager
     */
    private static $contentManager;

    /**
     * BackBee\Renderer\AbstractRenderer
     */
    private static $renderer;

    public static function setUpBeforeClass()
    {
        self::$kernel->resetDatabase();

        $user = self::$kernel->createAuthenticatedUser('content_persistence_test');

        self::$em->persist($user);
        self::$em->flush($user);

        self::$contentManager = self::$app->getContainer()->get('classcontent.manager');
        self::$contentManager->setBBUserToken(self::$app->getSecurityContext()->getToken());

        self::$renderer = self::$app->getRenderer();
    }

    public static function tearDownAfterClass()
    {
        self::$contentManager->setBBUserToken(null);
        self::$app->getSecurityContext()->setToken(null);
    }

    public function setUp()
    {
        $scriptDir = self::$app->getBBDir() . DIRECTORY_SEPARATOR .
                     'ClassContent' . DIRECTORY_SEPARATOR .
                     'Tests' . DIRECTORY_SEPARATOR .
                     'scripts';

        self::$renderer->addScriptDir($scriptDir);
    }

    public function testOnPostCallWithNoContent()
    {
        $data = ['foo'];

        $listener = new ClassContentListener();

        $request = Request::create('test', "GET");
        $response = Response::create(json_encode($data), 200, ['CONTENT_TYPE' => 'application/json']);

        $listener->onPostCall($this->createPostResponseEvent($response, $request));

        $this->assertEquals($response->getContent(), json_encode($data));
    }

    public function testOnPostCallWithRendermode()
    {
        $content = new MockContent();
        $content->mockedDefineParam(
            'rendermode',
            [
                'type'  => 'select',
                'value' => []
            ]
        );

        $listener = new ClassContentListener();
        $data = json_encode(self::$contentManager->jsonEncode($content, AbstractContent::JSON_CONCISE_FORMAT));

        $request = Request::create('test', "GET");
        $response = Response::create($data, 200, ['CONTENT_TYPE' => 'application/json']);

        $listener->onPostCall($this->createPostResponseEvent($response, $request));

        $responseContent = json_decode($response->getContent());

        $this->assertEquals(json_decode(json_encode(['default' => 'Default mode'])), $responseContent->parameters->rendermode->options);
    }

    public function testOnPostCallWithNoRendermodeParam()
    {
        $content = new MockContent();

        $listener = new ClassContentListener();
        $data = json_encode(self::$contentManager->jsonEncode($content, AbstractContent::JSON_CONCISE_FORMAT));

        $request = Request::create('test', "GET");
        $response = Response::create($data, 200, ['CONTENT_TYPE' => 'application/json']);

        $listener->onPostCall($this->createPostResponseEvent($response, $request));

        $responseContent = json_decode($response->getContent());

        $this->assertFalse(isset($responseContent->parameters->rendermode));
    }

    public function testOnPostCallWithRendermodeWithTextHtmlHeader()
    {
        $content = new MockContent();
        $content->mockedDefineParam(
            'rendermode',
            [
                'type'  => 'select',
                'value' => []
            ]
        );

        $listener = new ClassContentListener();
        $data = json_encode(self::$contentManager->jsonEncode($content, AbstractContent::JSON_CONCISE_FORMAT));

        $request = Request::create('test', "GET");
        $response = Response::create($data, 200, ['CONTENT_TYPE' => 'text/html']);

        $listener->onPostCall($this->createPostResponseEvent($response, $request));

        $responseContent = json_decode($response->getContent());

        $this->assertFalse(isset($responseContent->parameters->rendermode->options));
    }

    private function createPostResponseEvent($response, $request)
    {
        $postResponseEvent = new PostResponseEvent($response, $request);
        $postResponseEvent->setDispatcher(self::$app->getEventDispatcher());

        return $postResponseEvent;
    }
}
