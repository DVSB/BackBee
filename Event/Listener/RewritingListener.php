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

use BackBee\BBApplication;
use BackBee\ClassContent\AClassContent;
use BackBee\Event\Event;
use BackBee\NestedNode\Page;
use BackBee\Rewriting\IUrlGenerator;

/**
 * Listener to rewriting events.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class RewritingListener
{
    /**
     * Occur on classcontent.onflush events.
     *
     * @param \BackBee\Event\Event $event
     */
    public static function onFlushContent(Event $event)
    {
        $content = $event->getTarget();
        if (false === ($content instanceof AClassContent)) {
            return;
        }

        $page = $content->getMainNode();
        if (null === $page) {
            return;
        }

        $newEvent = new Event($page, $content);
        $newEvent->setDispatcher($event->getDispatcher());
        self::onFlushPage($newEvent);
    }

    /**
     * Occur on nestednode.page.onflush events.
     *
     * @param \BackBee\Event\Event $event
     */
    public static function onFlushPage(Event $event)
    {
        $page = $event->getTarget();
        if (false === ($page instanceof Page)) {
            return;
        }

        $maincontent = $event->getEventArgs();
        if (false === ($maincontent instanceof AClassContent)) {
            $maincontent = null;
        }

        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();

        if (true === self::_updateUrl($application, $page, $maincontent)) {
            foreach ($page->getChildren() as $descendant) {
                self::_updateUrl($application, $descendant);
            }
        }
    }

    /**
     * Update URL for a page and its descendants according to the application IUrlGenerator.
     *
     * @param \BackBee\BBApplication              $application
     * @param \BackBee\NestedNode\Page            $page
     * @param \BackBee\ClassContent\AClassContent $maincontent
     */
    private static function _updateUrl(BBApplication $application, Page $page, AClassContent $maincontent = null)
    {
        $url_generator = $application->getUrlGenerator();
        if (false === ($url_generator instanceof IUrlGenerator)) {
            return;
        }

        $em = $application->getEntityManager();
        if (null === $maincontent && 0 < count($url_generator->getDiscriminators())) {
            $maincontent = $em->getRepository('BackBee\ClassContent\AClassContent')
                ->getLastByMainnode($page, $url_generator->getDiscriminators())
            ;
        }

        $uow = $em->getUnitOfWork();
        $change_set = $uow->getEntityChangeSet($page);
        if (true === $uow->isScheduledForUpdate($page) && true === array_key_exists('_state', $change_set)) {
            $page->setOldState($change_set['_state'][0]);
        }

        $new_url = $url_generator->generate($page, $maincontent);
        if ($new_url !== $page->getUrl(false)) {
            $page->setUrl($new_url);

            if ($uow->isScheduledForInsert($page) || $uow->isScheduledForUpdate($page)) {
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata('BackBee\NestedNode\Page'), $page);
            } elseif (!$uow->isScheduledForDelete($page)) {
                $uow->computeChangeSet($em->getClassMetadata('BackBee\NestedNode\Page'), $page);
            }

            return true;
        }

        return false;
    }
}
