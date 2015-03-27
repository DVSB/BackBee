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

namespace BackBee\Controller\Tests;

use Symfony\Component\HttpFoundation\Request;
use BackBee\Controller\ControllerResolver;
use BackBee\Tests\TestCase;

/**
 * ControllerResolver Test.
 *
 * @category    BackBee
 *
 * @copyright   Lp system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Controller\ControllerResolver
 */
class ControllerResolverTest extends TestCase
{
    public function test__construct()
    {
        $resolver = new ControllerResolver($this->getBBApp());

        $this->assertInstanceOf('BackBee\Controller\ControllerResolver', $resolver);
    }

    /**
     * @covers ::getController
     * @covers ::createController
     */
    public function test_getController()
    {
        $resolver = new ControllerResolver($this->getBBApp());

        $request = new Request();
        $this->assertFalse($resolver->getController($request));
    }
}
