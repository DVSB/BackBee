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

namespace BackBuilder\Cache;

use Symfony\Component\HttpFoundation\Request;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
interface QueryParamStrategyManagerInterface
{
    /**
     * Constants which define cache query params strategy
     */
    const NONE_QPARAMS_STRATEGY = 0;
    const INCLUDE_EVERY_QPARAMS_STRATEGY = 1;
    const INCLUDE_CLASSCONTENT_QPARAMS_STRATEGY = 2;

    /**
     *
     *
     * @param  Request $request
     * @param  integer $strategy
     * @param  array   $options
     *
     * @return string
     */
    public function process(Request $request, $strategy, array $options = array());
}
