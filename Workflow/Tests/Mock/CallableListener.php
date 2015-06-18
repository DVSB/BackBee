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

/**
 * This listener allows us to know if it has been called or not and if yes, how many times.
 *
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class CallableListener
{
    /**
     * @var integer
     */
    private $callCount = 0;

    /**
     * Invoke this method to test if this listener has been called.
     */
    public function call()
    {
        $this->callCount++;
    }

    /**
     * Returns true if ::call() has been called, else false (no matter how many times it has been called).
     *
     * @return boolean
     */
    public function hasBeenCalled()
    {
        return 0 < $this->callCount;
    }

    /**
     * Returns how many times this listener has been called.
     *
     * @return integer
     */
    public function getCallCount()
    {
        return $this->callCount;
    }
}
