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

use BackBuilder\BBApplication,
    BackBuilder\Event\Event,
    BackBuilder\NestedNode\Page,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\Rewriting\IUrlGenerator;

/**
 * Listener to rewriting events
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class LocaleListener
{

    /**
     * Occur on classcontent.onflush events
     * @param \BackBuilder\Event\Event $event
     */
    public static function onFlushContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AClassContent))
            return;

        $page = $content->getMainNode();
        if (NULL === $page)
            return;

        $newEvent = new Event($page, $content);
        $newEvent->setDispatcher($event->getDispatcher());
        self::onFlushPage($newEvent);
    }

    /**
     * Occur on nestednode.page.onflush events
     * @param \BackBuilder\Event\Event $event
     */
    public static function onFlushPage(Event $event)
    {
        $page = $event->getTarget();
        if (!($page instanceof Page))
            return;

        $maincontent = $event->getEventArgs();
        if (!($maincontent instanceof AClassContent))
            $maincontent = NULL;

        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();
        $em = $application->getEntityManager();

        self::_updateUrl($application, $page, $maincontent);

        $descendants = $em->getRepository('BackBuilder\NestedNode\Page')->getDescendants($page);
        foreach ($descendants as $descendant) {
            self::_updateUrl($application, $descendant);
        }
    }

    /**
     * Update URL for a page and its descendants according to the application IUrlGenerator
     * 
     * @param \BackBuilder\BBApplication $application
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\ClassContent\AClassContent $maincontent
     */
    private static function _updateUrl(BBApplication $application, Page $page, AClassContent $maincontent = NULL)
    {
        $urlGenerator = $application->getUrlGenerator();
        if (!($urlGenerator instanceof IUrlGenerator))
            return;

        $em = $application->getEntityManager();
        if (NULL === $maincontent && 0 < count($urlGenerator->getDescriminators())) {
            $maincontent = $em->getRepository('BackBuilder\ClassContent\AClassContent')->getLastByMainnode($page, $urlGenerator->getDescriminators());
        }

        $newUrl = $urlGenerator->generate($page, $maincontent);
        if ($page->getUrl() != $newUrl) {
            $page->setUrl($newUrl);

            $uow = $em->getUnitOfWork();
            if ($uow->isScheduledForUpdate($page))
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata('BackBuilder\NestedNode\Page'), $page);
            elseif (!$uow->isScheduledForDelete($page))
                $uow->computeChangeSet($em->getClassMetadata('BackBuilder\NestedNode\Page'), $page);
        }
    }

}