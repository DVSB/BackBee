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

namespace BackBee\Event\Listener;

use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use BackBee\Security\Token\BBUserToken;

/*
 * Listener to theme events
 *
 * @category    BackBee
 * @package     BackBee\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class ThemeListener
{
    public static function onUserIsAuthenticated(AuthenticationEvent $event)
    {
        $application = $event->getDispatcher()->getApplication();
        $application->debug('User not Authenticated');

        if ($event->getAuthenticationToken() instanceof BBUserToken) {
            $application->getTheme()->init();
        } else {
            $application->getTheme()->init();
        }
    }

    public static function onUserIsNotAuthenticated(AuthenticationEvent $event)
    {
        $event->getDispatcher()->getApplication()->debug('User not Authenticated');
        $event->getDispatcher()->getApplication()->getTheme()->init();
    }
}
