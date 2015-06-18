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

namespace BackBee\Workflow\Tests\Mock;

use BackBee\Event\Event;
use BackBee\Workflow\ListenerInterface;

/**
 * This listener is used by test for BackBee\Workflow\State and BackBee\Workflow\Listener\PageListener.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class StateListener implements ListenerInterface
{
    private $switchOnStateCount = 0;
    private $switchOffStateCount = 0;

    /**
     * {@inheritdoc}
     */
    public function switchOnState(Event $event)
    {
        $this->switchOnStateCount++;
    }

    /**
     * {@inheritdoc}
     */
    public function switchOffState(Event $event)
    {
        $this->switchOffStateCount++;
    }

    public function hasSwitchOnStateBeenCalled()
    {
        return 0 < $this->switchOnStateCount;
    }

    public function hasSwitchOffStateBeenCalled()
    {
        return 0 < $this->switchOffStateCount;
    }

    public function getSwitchOnStateCount()
    {
        return $this->switchOnStateCount;
    }

    public function getSwitchOffStateCount()
    {
        return $this->switchOffStateCount;
    }
}
