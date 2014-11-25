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

namespace BackBuilder\NestedNode\Tests\Repository;

use BackBuilder\Tests\TestCase;
use BackBuilder\NestedNode\Section;
use BackBuilder\NestedNode\Page;
use BackBuilder\Site\Site;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Tests\Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class SectionRepositoryTest extends TestCase
{

    /**
     * @var \BackBuilder\NestedNode\Section
     */
    private $root;

    /**
     * @var \BackBuilder\NestedNode\Repository\SectionRepository
     */
    private $repo;

    /**
     * @covers \BackBuilder\NestedNode\Repository\SectionRepository::getRoot
     */
    public function testGetRoot()
    {
        $this->assertEquals($this->root, $this->repo->getRoot($this->root->getSite()));

        $new_site = new Site();
        $this->assertNull($this->repo->getRoot($new_site));
    }

    /**
     * Sets up the fixture
     */
    protected function setUp()
    {
        $this->application = $this->getBBApp();
        $em = $this->application->getEntityManager();

        $st = new SchemaTool($em);
        $st->createSchema(array($em->getClassMetaData('BackBuilder\NestedNode\Section')));
        $st->createSchema(array($em->getClassMetaData('BackBuilder\Site\Site')));

        $site = new Site('site_uid', array('label' => 'site mock'));
        $em->persist($site);

        $this->root = new Section('root_uid', array('site' => $site));
        $em->persist($this->root);

        $em->flush();

        $this->repo = $em->getRepository('BackBuilder\NestedNode\Section');
    }

}
