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

namespace BackBee\Tests\Mock;

use Faker\Factory;

/**
 * @category    BackBee
 *
 * @copyright   Lp system
 * @author      n.dufreche
 */
class MockEntityManager extends \PHPUnit_Framework_TestCase implements IMock
{
    private $faker;

    private $stub_container = array();

    public function __construct()
    {
        $this->faker = Factory::create();
        $this->faker->seed(1337);
        $this->faker->addProvider(new \BackBee\Tests\Fixtures\Provider\Theme($this->faker));
    }

    public function getRepository($namespace)
    {
        if (!array_key_exists($namespace, $this->stub_container)) {
            $exploded_namespace = explode('\\', $namespace);
            $this->stub_container[$namespace] = $this->{lcfirst(end($exploded_namespace)).'Stub'}($namespace);
        }

        return $this->stub_container[$namespace];
    }

    private function personalThemeEntityStub()
    {
        $theme = new \BackBee\Theme\PersonalThemeEntity($this->faker->themeEntity);

        $stub = $this->getMockBuilder('BackBee\Theme\Repository\ThemeRepository')->disableOriginalConstructor()->getMock();

        $stub->expects($this->any())
              ->method('retrieveBySiteUid')
              ->will($this->returnValue($theme));

        return $stub;
    }
}
