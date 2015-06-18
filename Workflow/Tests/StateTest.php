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

namespace BackBee\Workflow\Tests;

use BackBee\Workflow\State;
use BackBee\Workflow\Tests\Mock\StateListener;

/**
 * Tests for BackBee\Workflow\State.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class StateTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $state = new State();
        $this->assertNotNull($state->getUid());
        $this->assertNull($state->getCode());
        $this->assertNull($state->getLabel());

        $state = new State('test_state', [
            'code'  => 123,
            'label' => 'random label',
        ]);
        $this->assertSame('test_state', $state->getUid());
        $this->assertSame(123, $state->getCode());
        $this->assertSame('random label', $state->getLabel());
    }

    /**
     * @expectedException        \BackBee\Exception\InvalidArgumentException
     * @expectedExceptionMessage The code of a workflow state has to be an integer
     */
    public function testSetWrongCodeTypeThrowsException()
    {
        (new State())->setCode('123');
    }

    public function testSetListener()
    {
        $state = new State();
        $this->assertNull($state->getListener());
        $this->assertNull($state->getListenerInstance());

        $state->setListener('BackBee\Workflow\Tests\Mock\StateListener');
        $this->assertSame('BackBee\Workflow\Tests\Mock\StateListener', $state->getListener());
        $this->assertInstanceOf('BackBee\Workflow\Tests\Mock\StateListener', $state->getListenerInstance());

        $state->setListener(new StateListener());
        $this->assertSame('BackBee\Workflow\Tests\Mock\StateListener', $state->getListener());
        $this->assertInstanceOf('BackBee\Workflow\Tests\Mock\StateListener', $state->getListenerInstance());

        $state->setListener(null);
        $this->assertNull($state->getListener());
        $this->assertNull($state->getListenerInstance());
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Workflow state listener must be type of null, object or string, boolean given.
     */
    public function testSetInvalidListenerTypeThrowsException()
    {
        (new State())->setListener(true);
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage Workflow state listener must implement BackBee\Workflow\ListenerInterface.
     */
    public function testSetInvalidListenerThrowsException()
    {
        (new State())->setListener(new \stdClass());
    }

    public function testJsonSerialize()
    {
        $state = new State();

        $json = $state->jsonSerialize();

        $this->assertTrue(array_key_exists('uid', $json));
        $this->assertTrue(array_key_exists('layout_uid', $json));
        $this->assertTrue(array_key_exists('code', $json));
        $this->assertTrue(array_key_exists('label', $json));
    }
}
