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

namespace BackBee\Site\Fixture;

use Faker\Factory;
use BackBee\Installer\Annotation as BB;
use BackBee\Site\Site;

/**
 * @BB\Fixture
 */
class SiteFixture extends Site
{
    protected $faker;

    public function __construct($local = Factory::DEFAULT_LOCALE)
    {
        $this->faker = Factory::create($local);
    }

    public function setUp()
    {
        $site = new Site();
        $site->_label = $this->faker->domainWord;
        $site->_server_name = $this->faker->domainName;
        $site->_created = $site->_modified = new \DateTime();

        return $site;
    }
}
