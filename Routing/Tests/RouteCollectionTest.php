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

namespace BackBee\NestedNode\Tests;

use BackBee\Routing\RouteCollection;
use BackBee\Site\Site;
use BackBee\Tests\BackBeeTestCase;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class RouteCollectionTest extends BackBeeTestCase
{

    public function test__construct()
    {
        $this->assertInstanceOf('BackBee\Routing\RouteCollection', new RouteCollection);
        $this->assertInstanceOf('BackBee\Routing\RouteCollection', new RouteCollection(self::$app));
    }

    public function testGetUriWithoutApplication()
    {
        $routeCollection = new RouteCollection();
        $this->assertEquals('http://www.backbee.com', $routeCollection->getUri('http://www.backbee.com'));
        $this->assertEquals('/', $routeCollection->getUri());
        $this->assertEquals('/', $routeCollection->getUri(null, null, null, RouteCollection::IMAGE_URL));
        $this->assertEquals('/', $routeCollection->getUri(null, '.html'));
        $this->assertEquals('/fake', $routeCollection->getUri('/fake'));
        $this->assertEquals('/fake.htm', $routeCollection->getUri('fake.htm', '.html'));
        $this->assertEquals('/fake.html', $routeCollection->getUri('fake', '.html'));
        $this->assertEquals('/fake.html', $routeCollection->getUri('fake', null, new Site()));
    }

    public function testGetUriSapiWithoutSite()
    {
        $routeCollection = new RouteCollection(self::$app);
        $this->assertEquals('http://www.backbee.com', $routeCollection->getUri('http://www.backbee.com'));
        $this->assertEquals('/', $routeCollection->getUri());
        $this->assertEquals('/images/', $routeCollection->getUri(null, null, null, RouteCollection::IMAGE_URL));
        $this->assertEquals('/', $routeCollection->getUri(null, '.html'));
        $this->assertEquals('/fake', $routeCollection->getUri('/fake'));
        $this->assertEquals('/fake.htm', $routeCollection->getUri('fake.htm', '.html'));
        $this->assertEquals('/fake.html', $routeCollection->getUri('fake', '.html'));
    }

    public function testGetUriSapiWithSiteSetted()
    {
        $site = new Site();
        $site->setServerName('www.fakeserver.com');

        $routeCollection = new RouteCollection(self::$app);
        $this->assertEquals('http://www.backbee.com', $routeCollection->getUri('http://www.backbee.com', null, $site));
        $this->assertEquals('http://www.fakeserver.com/', $routeCollection->getUri(null, null, $site));
        $this->assertEquals('http://www.fakeserver.com/images/', $routeCollection->getUri(null, null, $site, RouteCollection::IMAGE_URL));
        $this->assertEquals('http://www.fakeserver.com/', $routeCollection->getUri(null, '.html', $site));
        $this->assertEquals('http://www.fakeserver.com/fake.html', $routeCollection->getUri('/fake', null, $site));
        $this->assertEquals('http://www.fakeserver.com/fake.htm', $routeCollection->getUri('fake.htm', '.html', $site));
        $this->assertEquals('http://www.fakeserver.com/fake.htm', $routeCollection->getUri('fake', '.htm', $site));
    }

    public function testGetUriSapiWithApplicationSite()
    {
        $site = new Site();
        $site->setServerName('www.fakeserver.com');
        self::$app->getContainer()->set('site', $site);

        $routeCollection = new RouteCollection(self::$app);
        $this->assertEquals('http://www.backbee.com', $routeCollection->getUri('http://www.backbee.com'));
        $this->assertEquals('http://www.fakeserver.com/', $routeCollection->getUri());
        $this->assertEquals('http://www.fakeserver.com/images/', $routeCollection->getUri(null, null, null, RouteCollection::IMAGE_URL));
        $this->assertEquals('http://www.fakeserver.com/', $routeCollection->getUri(null, '.html'));
        $this->assertEquals('http://www.fakeserver.com/fake.html', $routeCollection->getUri('/fake'));
        $this->assertEquals('http://www.fakeserver.com/fake.htm', $routeCollection->getUri('fake.htm', '.html'));
        $this->assertEquals('http://www.fakeserver.com/fake.htm', $routeCollection->getUri('fake', '.htm'));
    }

    public function testGetUriRequested()
    {
        // Starting with a site without servername and simulate an HTTPS request
        $site = new Site();
        self::$app->getContainer()->set('site', $site);
        unset($GLOBALS['argv']);
        self::$app->setIsStarted(true);

        $request = self::$app->getRequest();
        $request->server->add([
            'SCRIPT_URL' => '/public/fake/fake.html',
            'SCRIPT_URI' => 'https://www.fakeserver.com/public/fake/fake.html',
            'HTTP_HOST' => 'www.fakeserver.com',
            'SERVER_NAME' => 'www.fakeserver.com',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => '443',
            'HTTPS' => 'on',
            'DOCUMENT_ROOT' => '/home/web/fakeroot',
            'SCRIPT_FILENAME' => '/home/web/fakeroot/public/index.php',
            'REQUEST_URI' => '/public/fake/fake.html',
            'SCRIPT_NAME' => '/public/index.php'
        ]);

        // No site provided, the request is used
        $routeCollection = new RouteCollection(self::$app);
        $this->assertEquals('http://www.backbee.com', $routeCollection->getUri('http://www.backbee.com'));
        $this->assertEquals('https://www.fakeserver.com/public/', $routeCollection->getUri());
        $this->assertEquals('https://www.fakeserver.com/public/images/', $routeCollection->getUri(null, null, null, RouteCollection::IMAGE_URL));
        $this->assertEquals('https://www.fakeserver.com/public/', $routeCollection->getUri(null, '.html'));
        $this->assertEquals('https://www.fakeserver.com/public/fake.html', $routeCollection->getUri('/fake'));
        $this->assertEquals('https://www.fakeserver.com/public/fake.htm', $routeCollection->getUri('fake.htm', '.html'));
        $this->assertEquals('https://www.fakeserver.com/public/fake.html', $routeCollection->getUri('fake', '.html'));

        // A site is provided, the base URL and the protocol can't be predicted
        $otherSite = new Site();
        $otherSite->setServerName('other.fakeserver.com');
        $this->assertEquals('http://www.backbee.com', $routeCollection->getUri('http://www.backbee.com', null, $otherSite));
        $this->assertEquals('http://other.fakeserver.com/', $routeCollection->getUri(null, null, $otherSite));
        $this->assertEquals('http://other.fakeserver.com/images/', $routeCollection->getUri(null, null, $otherSite, RouteCollection::IMAGE_URL));
        $this->assertEquals('http://other.fakeserver.com/', $routeCollection->getUri(null, '.html', $otherSite));
        $this->assertEquals('http://other.fakeserver.com/fake.html', $routeCollection->getUri('/fake', null, $otherSite));
        $this->assertEquals('http://other.fakeserver.com/fake.htm', $routeCollection->getUri('fake.htm', '.html', $otherSite));
        $this->assertEquals('http://other.fakeserver.com/fake.html', $routeCollection->getUri('fake', '.html', $otherSite));
    }

    public function testRouteCollection()
    {
        $routes = [
            'default' => ['pattern' => '/', 'defaults' => []],
            'fake'    => ['pattern' => '/fake', 'defaults' => []],
            'novalid' => ['pattern' => '/'],
        ];

        $routeCollection = new RouteCollection(self::$app);
        $routeCollection->pushRouteCollection($routes);
        
        $this->assertEquals('/', $routeCollection->getRoutePath('default'));
        $this->assertEquals('/fake', $routeCollection->getRoutePath('fake'));
        $this->assertNull($routeCollection->getRoutePath('novalid'));
    }

    public function testGetUrlByRouteName()
    {
        $routes = [
            'fake'    => ['pattern' => '/fake/{param1}/{param2}', 'defaults' => []],
        ];

        // Starting with a site without servername and simulate an HTTPS request
        $site = new Site();
        self::$app->getContainer()->set('site', $site);
        unset($GLOBALS['argv']);
        self::$app->setIsStarted(true);

        $request = self::$app->getRequest();
        $request->server->add([
            'SCRIPT_URL' => '/public/fake/fake.html',
            'SCRIPT_URI' => 'https://www.fakeserver.com/public/fake/fake.html',
            'HTTP_HOST' => 'www.fakeserver.com',
            'SERVER_NAME' => 'www.fakeserver.com',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => '443',
            'HTTPS' => 'on',
            'DOCUMENT_ROOT' => '/home/web/fakeroot',
            'SCRIPT_FILENAME' => '/home/web/fakeroot/public/index.php',
            'REQUEST_URI' => '/public/fake/fake.html',
            'SCRIPT_NAME' => '/public/index.php'
        ]);

        $routeCollection = new RouteCollection(self::$app);
        $routeCollection->pushRouteCollection($routes);
        
        $this->assertEquals('https://www.fakeserver.com/public/fake/1/2.html', $routeCollection->getUrlByRouteName('fake', ['param1' => '1', 'param2' => '2']));
        $this->assertEquals('https://www.fakeserver.com/public/fake/1/2.html', $routeCollection->getUrlByRouteName('fake', ['param1' => '1', 'param2' => '2', 'param3' => '3']));
        $this->assertEquals('https://www.fakeserver.com/public/fake/1/2.html?param3=3', $routeCollection->getUrlByRouteName('fake', ['param1' => '1', 'param2' => '2', 'param3' => '3'], null, true, null, true));
        $this->assertEquals('https://www.fakeserver.com/public/test/fake/1/2.html', $routeCollection->getUrlByRouteName('fake', ['param1' => '1', 'param2' => '2'], '/test'));
        $this->assertEquals('https://www.fakeserver.com/public/fake/1/2', $routeCollection->getUrlByRouteName('fake', ['param1' => '1', 'param2' => '2'], null, false));
    }
}
