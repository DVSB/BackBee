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

namespace BackBee\Bundle\Listener;

use BackBee\Event\Event;

/**
 * BackBee core bundle listener
 *
 * @category    BackBee
 * @package     BackBee\Bundle
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class BundleListener
{
    /**
     * Occurs on `bbapplication.stop` event to stop every started bundles
     *
     * @param Event $event
     */
    public static function onApplicationStop(Event $event)
    {
        $application = $event->getTarget();
        foreach (array_keys($application->getContainer()->findTaggedServiceIds('bundle')) as $bundle_id) {
            if (true === $application->getContainer()->hasInstanceOf($bundle_id)) {
                $application->getContainer()->get($bundle_id)->stop();
            }
        }
    }
}
