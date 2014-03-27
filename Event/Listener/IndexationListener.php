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
    BackBuilder\Site\Site,
    BackBuilder\ClassContent\Indexation,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\Util\Doctrine\ScheduledEntities;
use Doctrine\ORM\EntityManager;

/**
 * Listener to indexation events
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class IndexationListener
{

    private static $_content_content_done = false;
    private static $_site_content_done = false;

    /**
     * Replaces the site-content indexes for the scheduled AClassContent
     * @param \Doctrine\ORM\EntityManager $em
     * @param \BackBuilder\Site\Site $site
     */
    private static function _updateIdxSiteContents(EntityManager $em, Site $site)
    {
        if (false === self::$_site_content_done) {
            $em->getRepository('BackBuilder\ClassContent\Indexes\IdxSiteContent')
                    ->replaceIdxSiteContents($site, ScheduledEntities::getScheduledAClassContentInsertions($em));

            self::$_site_content_done = true;
        }
    }

    /**
     * Replaces the content-content indexes for the scheduled AClassContent
     * @param \Doctrine\ORM\EntityManager $em
     */
    private static function _updateIdxContentContents(EntityManager $em)
    {
        if (false === self::$_content_content_done) {
            $em->getRepository('BackBuilder\ClassContent\Indexes\IdxContentContent')
                    ->replaceIdxContentContents(ScheduledEntities::getScheduledAClassContentNotForDeletions($em, true));

            self::$_content_content_done = true;
        }
    }

    /**
     * Subscriber to nestednode.page.onflush event
     *     - Replace page-content and site-content indexes if 
     *       the Page target is inserted or updated
     *     - Remove page-content and site-content if the Page target is deleted
     * @param \BackBuilder\Event\Event $event
     */
    public static function onFlushPage(Event $event)
    {
        $page = $event->getTarget();
        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();
        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();

        if ($uow->isScheduledForInsert($page) || $uow->isScheduledForUpdate($page)) {
            self::_updateIdxSiteContents($em, $page->getSite());
        } elseif ($uow->isScheduledForDeletion($page)) {
            // @todo
        }
    }

    public static function onFlushContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AClassContent))
            return;
        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();
        if (NULL === $application)
            return;

        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();

        if ($uow->isScheduledForInsert($content) && false === ScheduledEntities::hasScheduledPageNotForDeletions($em) && null !== $application->getSite()) {
            self::_updateIdxSiteContents($em, $application->getSite());
        }

        if ($uow->isScheduledForInsert($content) || $uow->isScheduledForUpdate($content)) {
            self::_updateIdxContentContents($em);

            if (is_array($content->getProperty()) && array_key_exists('indexation', $content->getProperty())) {
                foreach ($content->getProperty('indexation') as $indexedElement) {
                    $indexedElement = (array) $indexedElement;
                    $callback = array_key_exists(1, $indexedElement) ? $indexedElement[1] : NULL;

                    if ('@' === substr($indexedElement[0], 0, 1)) {
                        // parameter indexation
                        $param = substr($indexedElement[0], 1);
                        $value = $content->getParam($param);
                        $owner = $content;
                    } else {
                        $elements = explode('->', $indexedElement[0]);

                        $owner = NULL;
                        $element = NULL;
                        $value = $content;
                        foreach ($elements as $element) {
                            $owner = $value;
                            if (!$value instanceof \BackBuilder\ClassContent\AClassContent) {
                                continue;
                            }

                            if (NULL !== $value) {
                                $value = $value->getData($element);
                                if ($value instanceof AClassContent && false == $em->contains($value))
                                    $value = $em->find(get_class($value), $value->getUid());
                            }
                        }
                    }

                    if (NULL !== $callback) {
                        $callback = (array) $callback;
                        foreach ($callback as $func)
                            $value = call_user_func($func, $value);
                    }

                    if (NULL !== $owner && NULL !== $value) {
                        $index = $em->getRepository('BackBuilder\ClassContent\Indexation')->find(array('_content' => $content, '_field' => $indexedElement[0]));
                        if (NULL === $index) {
                            $index = new Indexation($content, $indexedElement[0], $owner, $value, serialize($callback));
                            $em->persist($index);
                        }
                        $index->setValue($value);
                        $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata('BackBuilder\ClassContent\Indexation'), $index);
                    }
                }

                $alreadyIndex = $em->getRepository('BackBuilder\ClassContent\Indexation')->findBy(array('_owner' => $content));
                foreach ($alreadyIndex as $index) {
                    $field = $index->getField();
                    $callback = unserialize($index->getCallback());

                    if (NULL !== $field) {
                        $tmp = explode('->', $field);
                        $field = array_pop($tmp);

                        try {
                            $value = $content->$field;

                            if (NULL !== $callback) {
                                $callback = (array) $callback;
                                foreach ($callback as $func) {
                                    $value = call_user_func($func, $value);
                                }
                            }

                            if ($value != $index->getValue() && NULL !== $value) {
                                $index->setValue($value);
                                $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata('BackBuilder\ClassContent\Indexation'), $index);
                            }
                        } catch (\Exception $e) {
                            // Nothing to do
                        }
                    }
                }
            }
        } elseif ($uow->isScheduledForDelete($content)) {
            if (null !== $site = $application->getSite()) {
                $em->getRepository('BackBuilder\ClassContent\Indexation')
                        ->removeIdxSiteContent($site, $content);
            }

            foreach ($content->getIndexation() as $index) {
                $em->remove($index);
                $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata('BackBuilder\ClassContent\Indexation'), $index);
            }
        }
    }

}
