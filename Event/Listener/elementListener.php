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

use BackBee\Event\Event;

/**
 * Listener to content element events
 *
 * @category    BackBee
 * @package     BackBee\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class elementListener
{
    public static function onRender(Event $event)
    {
        $dispatcher = $event->getDispatcher();
        $renderer = $event->getEventArgs();
        $renderer->assign('keyword', null);
        if (null !== $dispatcher) {
            $application = $dispatcher->getApplication();
            if (null === $application) {
                return;
            }
            $keywordloaded = $event->getTarget();
            if (!is_a($renderer, 'BackBee\Renderer\ARenderer')) {
                return;
            }
            $keyWord = $application->getEntityManager()->find('BackBee\NestedNode\KeyWord', $keywordloaded->value);
            if (!is_null($keyWord)) {
                $renderer->assign('keyword', $keyWord);
            }
        }
    }
}
