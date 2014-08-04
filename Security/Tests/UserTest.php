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

namespace BackBuilder\Security\Tests;

use BackBuilder\Security\User;

/**
 * Test for User class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 * 
 * @coversDefaultClass \BackBuilder\Security\User
 */
class UserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getRoles
     */
    public function testGetRoles()
    {
        $user = new User();
        
        $user->setApiKeyEnabled(true);
        $this->assertContains('ROLE_API_USER', $user->getRoles());
        
        $user->setActivated(false);
        $this->assertNotContains('ROLE_ACTIVE_USER', $user->getRoles());
        
        $user->setActivated(true);
        $this->assertContains('ROLE_ACTIVE_USER', $user->getRoles());
    }

}