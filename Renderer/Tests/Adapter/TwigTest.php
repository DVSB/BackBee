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

namespace BackBee\Renderer\Tests\Adapter;

use BackBee\Renderer\Tests\Mock\TwigAdapter as Twig;
use BackBee\Renderer\Renderer;
use BackBee\Tests\BackBeeTestCase;

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class TwigTest extends BackBeeTestCase
{
    public function testPassingConfigToConstructor()
    {
        $renderer = new Renderer(self::$app);
        $twig = new Twig($renderer);
        $this->assertFalse($twig->getEngine()->isStrictVariables());

        $twig = new Twig($renderer, [
            'strict_variables' => true,
        ]);
        $this->assertTrue($twig->getEngine()->isStrictVariables());
    }
}
