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

use BackBee\Tests\BackBeeTestCase;
use BackBee\Workflow\State;
use BackBee\Workflow\Tests\Mock\CallableListener;

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class PageListenerTest extends BackBeeTestCase
{
    public static function setUpBeforeClass()
    {
        self::$kernel->resetDatabase();

        $layout = self::$kernel->createLayout('test_workflow_page_listener');
        self::$em->persist($layout);
        self::$em->flush($layout);

        $state1 = new State('state_1', [
            'code'  => -8000,
            'label' => 'State 1',
        ]);
        $state2 = new State('state_2', [
            'code'  => 8000,
            'label' => 'State 2',
        ]);

        $state1->setListener('BackBee\Workflow\Tests\Mock\StateListener');
        $state2->setListener('BackBee\Workflow\Tests\Mock\StateListener');

        $state1->setLayout($layout);
        $state2->setLayout($layout);

        self::$em->persist($state1);
        self::$em->flush($state1);
        self::$em->persist($state2);
        self::$em->flush($state2);

        $page = self::$kernel->createPage('workflow_test');
        $page->setLayout($layout);
        $page->getParent()->setLayout($layout);
        self::$em->persist($page->getParent());
        self::$em->flush($page->getParent());
        self::$em->persist($page);
        self::$em->flush($page);
    }

    public function setUp()
    {
        self::$em->clear();
    }

    public function testDispatchPutOnlineAndPutOfflineEvents()
    {
        $dispatcher = self::$app->getEventDispatcher();

        $putOnlineListener = new CallableListener();
        $putOfflineListener = new CallableListener();

        $dispatcher->addListener('nestednode.page.putonline', [$putOnlineListener, 'call']);
        $dispatcher->addListener('nestednode.page.putoffline', [$putOfflineListener, 'call']);

        $this->assertFalse($putOnlineListener->hasBeenCalled());
        $this->assertFalse($putOfflineListener->hasBeenCalled());

        $page = self::$em->find('BackBee\NestedNode\Page', 'workflow_test');
        $page->setState(1);
        self::$em->flush($page);

        $this->assertTrue($putOnlineListener->hasBeenCalled());
        $this->assertFalse($putOfflineListener->hasBeenCalled());

        $page->setState(0);
        self::$em->flush($page);

        $this->assertSame(1, $putOnlineListener->getCallCount());
        $this->assertTrue($putOfflineListener->hasBeenCalled());

        $dispatcher->removeListener('nestednode.page.putonline', $putOnlineListener);
        $dispatcher->removeListener('nestednode.page.putoffline', $putOfflineListener);
    }

    public function testCallOfStateListenerSwitchToStateAndSwitchOutOfState()
    {
        $state1 = self::$em->find('BackBee\Workflow\State', 'state_1');
        $state1Listener = $state1->getListenerInstance();
        $this->assertFalse($state1Listener->hasSwitchOnStateBeenCalled());
        $this->assertFalse($state1Listener->hasSwitchOffStateBeenCalled());

        $page = self::$em->find('BackBee\NestedNode\Page', 'workflow_test');
        $page->setWorkflowState($state1);
        self::$em->flush($page);

        $this->assertTrue($state1Listener->hasSwitchOnStateBeenCalled());
        $this->assertSame(1, $state1Listener->getSwitchOnStateCount());
        $this->assertFalse($state1Listener->hasSwitchOffStateBeenCalled());

        $state2 = self::$em->find('BackBee\Workflow\State', 'state_2');
        $state2Listener = $state2->getListenerInstance();
        $this->assertFalse($state2Listener->hasSwitchOnStateBeenCalled());
        $this->assertFalse($state2Listener->hasSwitchOffStateBeenCalled());

        $page->setWorkflowState($state2);
        self::$em->flush($page);

        $this->assertSame(1, $state1Listener->getSwitchOnStateCount());
        $this->assertTrue($state1Listener->hasSwitchOffStateBeenCalled());
        $this->assertSame(1, $state1Listener->getSwitchOffStateCount());

        $this->assertTrue($state2Listener->hasSwitchOnStateBeenCalled());
        $this->assertSame(1, $state2Listener->getSwitchOnStateCount());
        $this->assertFalse($state2Listener->hasSwitchOffStateBeenCalled());
    }
}
