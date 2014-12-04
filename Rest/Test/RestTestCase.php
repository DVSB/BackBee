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

namespace BackBuilder\Rest\Test;

use BackBuilder\ApiClient\Auth\PrivateKeyAuth;
use BackBuilder\Security\User;
use BackBuilder\Tests\TestCase;

use Symfony\Component\HttpFoundation\Request;

/**
 * Test Case for REST
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class RestTestCase extends TestCase
{
    protected static $restUser;

    /**
     * POST request helper
     *
     * @param type $uri
     * @param array $data
     * @param type $contentType
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected static function requestPost($uri, array $data = [], $contentType = 'application/json', $sign = false)
    {
        $request = new Request([], $data, [], [], [], ['REQUEST_URI' => $uri, 'CONTENT_TYPE' => $contentType, 'REQUEST_METHOD' => 'POST'] );

        if($sign) {
            self::signRequest($request);
        }

        return $request;
    }

    /**
     * PUT request helper
     *
     * @param type $uri
     * @param array $data
     * @param type $contentType
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected static function requestPut($uri, array $data = [], $contentType = 'application/json', $sign = false)
    {
        $request = new Request([], $data, [], [], [], ['REQUEST_URI' => $uri, 'CONTENT_TYPE' => $contentType, 'REQUEST_METHOD' => 'PUT'] );

        if ($sign) {
            self::signRequest($request);
        }

        return $request;
    }

    /**
     * PATCH request helper
     *
     * @param type $uri
     * @param array $operations an array of PATCH operations
     * @param type $contentType
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected static function requestPatch($uri, array $operations = [], $contentType = 'application/json', $sign = false)
    {
        $request = new Request([], $operations, [], [], [], ['REQUEST_URI' => $uri, 'CONTENT_TYPE' => $contentType, 'REQUEST_METHOD' => 'PATCH'] );

        if ($sign) {
            self::signRequest($request);
        }

        return $request;
    }

    /**
     * Get request helper
     *
     * @param type $uri
     * @param array $data
     * @param type $contentType
     * @param array $headers
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected static function requestGet($uri, array $filters = [], $sign = false)
    {
        $request = new Request($filters, [], [], [], [], ['REQUEST_URI' => $uri, 'REQUEST_METHOD' => 'GET'] );

        if ($sign) {
            self::signRequest($request);
        }

        return $request;
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param BackBuilder\Security\User $user
     * @return self
     */
    protected static function signRequest(Request $request, User $user = null)
    {
        if (null === $user) {
            $user = self::$restUser;
        }

        $auth = new PrivateKeyAuth();
        $auth->setPrivateKey($user->getApiKeyPrivate());
        $auth->setPublicKey($user->getApiKeyPublic());
        $request->headers->add([
            PrivateKeyAuth::AUTH_PUBLIC_KEY_TOKEN => $user->getApiKeyPublic(),
            PrivateKeyAuth::AUTH_SIGNATURE_TOKEN => $auth->getRequestSignature($request->getMethod(), $request->getRequestUri())
        ]);

        return self;
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \BackBuilder\Security\User $apiUser
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function sendRequest(Request $request, User $apiUser = null)
    {
        if (null !== $apiUser) {
            self::sendRequest($request, $apiUser);
        }

        return $this->getBBApp()->getController()->handle($request);
    }

    protected function setUp()
    {
        $this->initAutoload();
        $bbapp = $this->getBBApp();
        $this->initDb($bbapp);
        $this->initAcl();
        $this->getBBApp()->setIsStarted(true);

        // create a default user for authentication
        self::$restUser = $this->createAuthUser('api', []);
    }

    protected function tearDown()
    {
        $this->dropDb($this->getBBApp());
        $this->getBBApp()->stop();
    }

}
