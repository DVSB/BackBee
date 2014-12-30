<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\Event\Listener;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for listeners that are enabled for a certain request path only
 *
 * @category    BackBee
 * @package     BackBee\Event
 * @copyright   Lp digital system
 * @author      k.golovin
 */
interface IPathEnabledListener
{
    /**
     * @param $path - route path for which this listener will be enabled
     */
    public function setPath($path);

    /**
     * @param  Request $request
     * @return boolean - true if the listener should be enabled for the $request
     */
    public function isEnabled(Request $request = null);
}
