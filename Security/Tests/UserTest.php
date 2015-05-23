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

namespace BackBee\Security\Tests;

use BackBee\Security\User;
use BackBee\Tests\TestCase;

/**
 * Test for User entity.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\Security\User
 */
class UserTest extends TestCase
{

    /**
     * Mock user
     * @var \BackBee\Security\User 
     */
    private $mockUser;

    /**
     * @covers ::__construct
     */
    public function test__construct()
    {
        $user = new User();
        $this->assertEquals('', $user->getLogin());
        $this->assertEquals('', $user->getPassword());
        $this->assertInstanceOf('\DateTime', $user->getCreated());
        $this->assertInstanceOf('\DateTime', $user->getModified());
        $this->assertInstanceOf('\Doctrine\Common\Collections\ArrayCollection', $user->getGroups());
        $this->assertInstanceOf('\Doctrine\Common\Collections\ArrayCollection', $user->getRevisions());

        $this->assertEquals('login', $this->mockUser->getLogin());
        $this->assertEquals('password', $this->mockUser->getPassword());
        $this->assertEquals('firstname', $this->mockUser->getFirstname());
        $this->assertEquals('firstname', $this->mockUser->getFirstname());
        $this->assertEquals('lastname', $this->mockUser->getLastname());
    }

    /**
     * @covers ::__toString
     */
    public function test__toString()
    {
        $this->assertEquals('firstname lastname (login)', $this->mockUser->__toString());
    }

    /**
     * @covers ::serialize
     */
    public function testSerialize()
    {
        $this->assertEquals('{"username":"login","commonname":"firstname lastname"}', $this->mockUser->serialize());
    }

    /**
     * @covers ::generateRandomApiKey
     * @covers ::generateApiPublicKey
     */
    public function testGenerateRandomApiKey()
    {
        $this->mockUser->generateRandomApiKey();
        $this->assertNotNull($this->mockUser->getApiKeyPrivate());
        $this->assertEquals(
                sha1($this->mockUser->getCreated()->format(\DateTime::ATOM) . $this->mockUser->getApiKeyPrivate()),
                $this->mockUser->getApiKeyPublic()
        );
    }

    /**
     * @covers ::checkPublicApiKey
     * @covers ::generateApiPublicKey
     */
    public function testCheckPublicApiKey()
    {
        $this->assertFalse($this->mockUser->checkPublicApiKey(''));

        $expected = sha1($this->mockUser->getCreated()->format(\DateTime::ATOM) . $this->mockUser->getApiKeyPrivate());
        $this->assertTrue($this->mockUser->checkPublicApiKey($expected));
    }

    /**
     * @covers ::setApiKeyEnabled
     * @covers ::generateKeysOnNeed
     */
    public function testSetApiKeyEnabled()
    {
        $this->assertNull($this->mockUser->getApiKeyPrivate());
        $this->assertNull($this->mockUser->getApiKeyPublic());

        $this->mockUser->setApiKeyEnabled(true);
        $privateKey = $this->mockUser->getApiKeyPrivate();
        $publicKey = $this->mockUser->getApiKeyPublic();

        $this->assertNotNull($privateKey);
        $this->assertNotNull($publicKey);

        $this->mockUser->setApiKeyEnabled(false);
        $this->assertEquals($privateKey, $this->mockUser->getApiKeyPrivate());
        $this->assertEquals($publicKey, $this->mockUser->getApiKeyPublic());
    }

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        $this->mockUser = new User('login', 'password', 'firstname', 'lastname');
    }

}
