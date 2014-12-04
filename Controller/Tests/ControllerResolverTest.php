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

namespace BackBuilder\Controller\Tests;

use Symfony\Component\HttpFoundation\Request;
use BackBuilder\Controller\ControllerResolver;
use BackBuilder\Tests\TestCase;

/**
 * ControllerResolver Test
 *
 * @category    BackBuilder
 * @package     BackBuilder\Controller
 * @copyright   Lp system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBuilder\Controller\ControllerResolver
 */
class ControllerResolverTest extends TestCase
{
    public function test__construct()
    {
        $resolver = new ControllerResolver($this->getBBApp());

        $this->assertInstanceOf('BackBuilder\Controller\ControllerResolver', $resolver);
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
