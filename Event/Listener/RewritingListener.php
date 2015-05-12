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
use BackBee\ClassContent\AbstractClassContent;
use BackBee\Event\Event;
use BackBee\NestedNode\Page;
use BackBee\Rewriting\UrlGeneratorInterface;

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
        if (!($content instanceof AbstractClassContent)) {
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
        if (!($page instanceof Page)) {
            return;
        }

        $maincontent = $event->getEventArgs();
        if (!($maincontent instanceof AbstractClassContent)) {
            $maincontent = null;
        }

        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();

        if (self::updateUrl($application, $page, $maincontent)) {
            foreach ($page->getChildren() as $descendant) {
                self::updateUrl($application, $descendant);
            }
        }
    }

    /**
     * Update URL for a page and its descendants according to the application UrlGeneratorInterface.
     *
     * @param BBApplication        $application
     * @param Page                 $page
     * @param AbstractClassContent $maincontent
     */
    private static function updateUrl(BBApplication $application, Page $page, AbstractClassContent $maincontent = null)
    {
        $urlGenerator = $application->getUrlGenerator();

        $em = $application->getEntityManager();
        if (null === $maincontent && 0 < count($urlGenerator->getDiscriminators())) {
            $maincontent = $em->getRepository('BackBee\ClassContent\AbstractClassContent')
                ->getLastByMainnode($page, $urlGenerator->getDiscriminators())
            ;
        }

        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($page);

        $newUrl = null;
        $url = $page->getUrl(false);
        if (isset($changeSet['_url']) && !empty($url)) {
            $newUrl = $urlGenerator->getUniqueness($page, $page->getUrl());
        } else {
            $force = isset($changeSet['_state']) && !($changeSet['_state'][0] & Page::STATE_ONLINE);
            $newUrl = $urlGenerator->generate($page, $maincontent, $force);
        }

        if ($newUrl !== $page->getUrl(false)) {
            $page->setUrl($newUrl);

            $classMetadata = $em->getClassMetadata('BackBee\NestedNode\Page');
            if ($uow->isScheduledForInsert($page) || $uow->isScheduledForUpdate($page)) {
                $uow->recomputeSingleEntityChangeSet($classMetadata, $page);
            } elseif (!$uow->isScheduledForDelete($page)) {
                $uow->computeChangeSet($classMetadata, $page);
            }

            return true;
        }

        return false;
    }
}
