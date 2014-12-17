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

use BackBee\Tests\TestCase;
use BackBee\Security\Acl\Loader\YmlLoader;
use BackBee\Security\Group;
use BackBee\Site\Site;

/**
 * Test for YmlLoader class
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @subpackage  Tests
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Security\Acl\Loader\YmlLoader
 */
class YmlLoaderTest extends TestCase
{
    protected $bbapp;
    protected $siteDefault;

    protected function setUp()
    {
        $this->bbapp = $this->getBBApp();
        $this->initDb($this->bbapp);
        $this->initAcl();

        $superAdminGroup = new Group();
        $superAdminGroup
            ->setIdentifier('super_admin')
            ->setName('Super Admin')
        ;
        $this->bbapp->getEntityManager()->persist($superAdminGroup);

        $adminGroup = new Group();
        $adminGroup
            ->setIdentifier('admin_front')
            ->setName('Super Admin')
        ;
        $this->bbapp->getEntityManager()->persist($adminGroup);

        $this->siteDefault = new Site();
        $this->siteDefault->setLabel('default');
        $this->getBBApp()->getEntityManager()->persist($this->siteDefault);
        $this->getBBApp()->getEntityManager()->flush();

        $loader = new YmlLoader();
        $loader->setContainer($this->getBBApp()->getContainer());
        $loader->load(file_get_contents(__DIR__.'/acl.yml'));
    }

    /**
     * @covers ::load
     */
    public function testLoad_superadmin()
    {
        $this->createAuthUser('super_admin');

        $this->assertTrue($this->getSecurityContext()->isGranted('VIEW', $this->siteDefault));
        $this->assertTrue($this->getSecurityContext()->isGranted('EDIT', $this->siteDefault));
    }

    /**
     * @covers ::load
     */
    public function testLoad_admin()
    {
        $this->createAuthUser('admin_front');

        $this->assertTrue($this->getSecurityContext()->isGranted('VIEW', $this->siteDefault));
        $this->assertFalse($this->getSecurityContext()->isGranted('EDIT', $this->siteDefault));
    }

    protected function tearDown()
    {
        //$this->dropDb();
    }
}
