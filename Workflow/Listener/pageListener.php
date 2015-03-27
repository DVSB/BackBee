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

namespace BackBee\Workflow\Listener;

use BackBee\Event\Event;

/**
 * Listener to page events.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      d.Bensid <djoudi.bensid@lp-digital.fr>
 */
class pageListener
{
    /**
     * Occur on nestednode.page.preupdate events.
     *
     * @access public
     *
     * @param Event $event
     */
    public static function onPreUpdate(Event $event)
    {
        $page = $event->getTarget();
        $eventArgs = $event->getEventArgs();

        if (!is_a($page, 'BackBee\NestedNode\Page')) {
            return;
        }

        if (is_a($eventArgs, 'Doctrine\ORM\Event\PreUpdateEventArgs')) {
            if ($eventArgs->hasChangedField('_workflow_state')) {
                $old = $eventArgs->getOldValue('_workflow_state');
                $new = $eventArgs->getNewValue('_workflow_state');

                if (null !== $new && null !== $new->getListener()) {
                    $new->getListener()->arrivedInState($event);
                }
                if (null !== $old && null !== $old->getListener()) {
                    $old->getListener()->outInState($event);
                }
            }

            if ($eventArgs->hasChangedField('_state')) {
                if (!($eventArgs->getOldValue('_state') & \BackBee\NestedNode\Page::STATE_ONLINE) &&
                        $eventArgs->getNewValue('_state') & \BackBee\NestedNode\Page::STATE_ONLINE) {
                    $event->getDispatcher()->triggerEvent('putonline', $page);

                    if (null === $page->getPublishing()) {
                        $em = $event->getApplication()
                                ->getEntityManager();

                        $datetime = new \DateTime();
                        $page->setPublishing($datetime);
                        $page->setModified($datetime);

                        $em->getUnitOfWork()
                                ->recomputeSingleEntityChangeSet($em->getClassMetadata('BackBee\NestedNode\Page'), $page);
                    }
                } elseif ($eventArgs->getOldValue('_state') & \BackBee\NestedNode\Page::STATE_ONLINE &&
                        !($eventArgs->getNewValue('_state') & \BackBee\NestedNode\Page::STATE_ONLINE)) {
                    $event->getDispatcher()->triggerEvent('putoffline', $page);
                }
            }
        }
    }
}
