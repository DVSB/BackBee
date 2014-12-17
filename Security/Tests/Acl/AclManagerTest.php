<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Security\Tests;

use BackBee\Security\Acl\AclManager;
use BackBee\Security\Group;
use BackBee\Tests\TestCase;
use BackBee\Security\Acl\Permission\PermissionMap;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * Test for AclManager
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Acl\AclManager
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

        $manager->updateClassAce(new \stdClass(), new UserSecurityIdentity('test', 'BackBee\Security\Group'), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::updateClassAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_updateClassAce_invalidSecurityIdentity()
    {
        $manager = $this->getAclManager();

        $manager->updateClassAce(new ObjectIdentity('test', 'BackBee\Security\Group'), new \stdClass(), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::updateObjectAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_updateObjectAce_invalidObjectIdentity()
    {
        $manager = $this->getAclManager();

        $manager->updateObjectAce(new \stdClass(), new UserSecurityIdentity('test', 'BackBee\Security\Group'), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::updateObjectAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_updateObjectAce_invalidSecurityIdentity()
    {
        $manager = $this->getAclManager();

        $manager->updateObjectAce(new ObjectIdentity('test', 'BackBee\Security\Group'), new \stdClass(), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::insertOrUpdateObjectAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_insertOrUpdateObjectAce_invalidObjectIdentity()
    {
        $manager = $this->getAclManager();

        $manager->insertOrUpdateObjectAce(new \stdClass(), new UserSecurityIdentity('test', 'BackBee\Security\Group'), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::insertOrUpdateObjectAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_insertOrUpdateObjectAce_invalidSecurityIdentity()
    {
        $manager = $this->getAclManager();

        $manager->insertOrUpdateObjectAce(new ObjectIdentity('test', 'BackBee\Security\Group'), new \stdClass(), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::insertOrUpdateClassAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_insertOrUpdateClassAce_invalidObjectIdentity()
    {
        $manager = $this->getAclManager();

        $manager->insertOrUpdateClassAce(new \stdClass(), new UserSecurityIdentity('test', 'BackBee\Security\Group'), PermissionMap::PERMISSION_VIEW);
    }

    /**
     * @covers ::insertOrUpdateClassAce
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object must implement IObjectIdentifiable
     */
    public function test_insertOrUpdateClassAce_invalidSecurityIdentity()
    {
        $manager = $this->getAclManager();

        $manager->insertOrUpdateClassAce(new ObjectIdentity('test', 'BackBee\Security\Group'), new \stdClass(), PermissionMap::PERMISSION_VIEW);
    }
}
