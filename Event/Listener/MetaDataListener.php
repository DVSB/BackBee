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

namespace BackBuilder\Event\Listener;

use BackBuilder\Event\Event,
    BackBuilder\NestedNode\Page,
    BackBuilder\ClassContent\AClassContent;

/**
 * Listener to metadata events
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MetaDataListener
{
    private static $onFlushPageAlreadyCalled = false;

    /**
     * Occur on classcontent.onflush events
     * @param \BackBuilder\Event\Event $event
     */
    public static function onFlushContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AClassContent)) {
            return;
        }

        $application = $event->getApplication();
        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();
        if ($uow->isScheduledForDelete($content)) {
            return;
        }
        
        if (null !== $page = $content->getMainNode()) {
            if (null !== $page->getMetaData()) {
                $newEvent = new Event($page, $content);
                $newEvent->setDispatcher($event->getDispatcher());
                self::onFlushPage($newEvent);
            }
        }
//
//        foreach($content->getParentContent() as $parent) {
//            if (null !== $page = $parent->getMainNode()) {
//                $newEvent = new Event($page, $parent);
//                $newEvent->setDispatcher($event->getDispatcher());
//                self::onFlushPage($newEvent);
//            }
//
//            $newEvent = new Event($parent);
//            $newEvent->setDispatcher($event->getDispatcher());
//            self::onFlushContent($newEvent);
//        }
    }

    /**
     * Occur on nestednode.page.onflush events
     * @param \BackBuilder\Event\Event $event
     */
    public static function onFlushPage(Event $event)
    {
        if (true === self::$onFlushPageAlreadyCalled) {
            return;
        }

        $page = $event->getTarget();
        if (!($page instanceof Page)) {
            return;
        }

        $application = $event->getApplication();
        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();
        if ($uow->isScheduledForDelete($page)) {
            return;
        }

        if (null === $metadata_config = $application->getTheme()->getConfig()->getSection('metadata')) {
            $metadata_config = $application->getConfig()->getSection('metadata');
        }
        
        if (null === $metadata = $page->getMetaData()) {
            $metadata = new \BackBuilder\MetaData\MetaDataBag($metadata_config, $page);
        } else {
            $metadata->update($metadata_config, $page);
        }
        
        $page->setMetaData($metadata->compute($page));

        if ($uow->isScheduledForInsert($page) || $uow->isScheduledForUpdate($page)) {
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata('BackBuilder\NestedNode\Page'), $page);
        } elseif (!$uow->isScheduledForDelete($page)) {
            $uow->computeChangeSet($em->getClassMetadata('BackBuilder\NestedNode\Page'), $page);
        }

        self::$onFlushPageAlreadyCalled = true;
    }

}
