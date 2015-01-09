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

use BackBuilder\Security\Acl\AclManager;
use BackBuilder\Security\Group;
use BackBuilder\Tests\TestCase;
use BackBuilder\Security\Acl\Permission\PermissionMap;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * Test for AclManager
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBuilder\Security\Acl\AclManager
 */
class AclManagerTest extends TestCase
{
    private $manager;

    /**
     * @covers ::getAcl
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_getAcl_invalidObjectIdentity()
    {
        $manager = $this->getAclManager();

        $manager->getAcl(new \stdClass());
    }

    /**
     * @covers ::updateClassAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_updateClassAce_invalidObjectIdentity()
    {
        $manager = $this->getAclManager();

        $manager->updateClassAce(new \stdClass(), new UserSecurityIdentity('test', 'BackBuilder\Security\Group'), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::updateClassAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_updateClassAce_invalidSecurityIdentity()
    {
        $manager = $this->getAclManager();

        $manager->updateClassAce(new ObjectIdentity('test', 'BackBuilder\Security\Group'), new \stdClass(), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::updateObjectAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_updateObjectAce_invalidObjectIdentity()
    {
        $manager = $this->getAclManager();

        $manager->updateObjectAce(new \stdClass(), new UserSecurityIdentity('test', 'BackBuilder\Security\Group'), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::updateObjectAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_updateObjectAce_invalidSecurityIdentity()
    {
        $manager = $this->getAclManager();

        $manager->updateObjectAce(new ObjectIdentity('test', 'BackBuilder\Security\Group'), new \stdClass(), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::insertOrUpdateObjectAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_insertOrUpdateObjectAce_invalidObjectIdentity()
    {
        $manager = $this->getAclManager();

        $manager->insertOrUpdateObjectAce(new \stdClass(), new UserSecurityIdentity('test', 'BackBuilder\Security\Group'), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::insertOrUpdateObjectAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_insertOrUpdateObjectAce_invalidSecurityIdentity()
    {
        $manager = $this->getAclManager();

        $manager->insertOrUpdateObjectAce(new ObjectIdentity('test', 'BackBuilder\Security\Group'), new \stdClass(), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::insertOrUpdateClassAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_insertOrUpdateClassAce_invalidObjectIdentity()
    {
        $manager = $this->getAclManager();

        $manager->insertOrUpdateClassAce(new \stdClass(), new UserSecurityIdentity('test', 'BackBuilder\Security\Group'), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::insertOrUpdateClassAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_insertOrUpdateClassAce_invalidSecurityIdentity()
    {
        $manager = $this->getAclManager();

        $manager->insertOrUpdateClassAce(new ObjectIdentity('test', 'BackBuilder\Security\Group'), new \stdClass(), PermissionMap::PERMISSION_VIEW);
    }
}
