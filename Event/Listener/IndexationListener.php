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
    BackBuilder\ClassContent\Indexation,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\Util\Doctrine\ScheduledEntities;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener to indexation events
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class IndexationListener implements EventSubscriberInterface
{

    /**
     * The current application instance
     * @var \BackBuilder\BBApplication
     */
    private static $_application;

    /**
     * The current entity manager
     * @var \Doctrine\ORM\EntityManager
     */
    private static $_em;

    /**
     * The content to be indexed
     * @var \BackBuilder\ClassContent\AClassContent
     */
    private static $_content;

    /**
     * Array of content uids already treated
     * @var type
     */
    private static $_content_content_done = array();

    /**
     * Array of content uids already treated
     * @var type
     */
    private static $_site_content_done = array();

    /**
     * Updates the site-content indexes for the scheduled AClassContent
     * @param array $contents_inserted
     * @param array $contents_removed
     */
    private static function _updateIdxSiteContents(array $contents_inserted, array $contents_removed)
    {
        if (null === $site = self::$_application->getSite()) {
            return;
        }

        self::$_em->getRepository('BackBuilder\ClassContent\Indexes\IdxContentContent')
                ->replaceIdxSiteContents($site, array_diff($contents_inserted, self::$_site_content_done))
                ->removeIdxSiteContents($site, $contents_removed);

        self::$_site_content_done = array_merge(self::$_site_content_done, $contents_inserted);
    }

    /**
     * Updates the content-content indexes for the scheduled AClassContent
     * @param array $contents_saved
     * @param array $contents_removed
     */
    private static function _updateIdxContentContents(array $contents_saved, array $contents_removed)
    {
        if (null === self::$_application->getSite()) {
            return;
        }

        self::$_em->getRepository('BackBuilder\ClassContent\Indexes\IdxContentContent')
                ->replaceIdxContentContents(array_diff($contents_saved, self::$_content_content_done))
                ->removeIdxContentContents($contents_removed);

        self::$_content_content_done = array_merge(self::$_content_content_done, $contents_saved);
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

    }

    /**
     * Checks the event validity
     * @param \BackBuilder\Event\Event $event
     * @return boolean
     */
    private static function _checkContentEvent(Event $event)
    {
        self::$_content = $event->getTarget();
        return self::$_content instanceof AClassContent;
    }

    /**
     * Returns the current application
     * @param \BackBuilder\Event\Event $event
     * @return \BackBuilder\BBApplication
     */
    private static function _getApplication(Event $event)
    {
        if (null === self::$_application) {
            if (null !== $event->getDispatcher()) {
                self::$_application = $event->getDispatcher()->getApplication();
            }
        }

        return self::$_application;
    }

    /**
     * Returns the current entity manager
     * @param \BackBuilder\Event\Event $event
     * @return \Doctrine\ORM\EntityManager
     */
    private static function _getEntityManager(Event $event)
    {
        if (null === self::$_em) {
            if (null !== self::_getApplication($event)) {
                self::$_em = self::_getApplication($event)->getEntityManager();
            }
        }

        return self::$_em;
    }

    /**
     * Updates every indexed value assocatied to the contents flushed
     * @param \BackBuilder\Event\Event $event
     */
    public static function onFlushContent(Event $event)
    {
        if (null === self::_getEntityManager($event)) {
            return;
        }

        $contents_inserted = ScheduledEntities::getScheduledAClassContentInsertions(self::$_em, true, true);
        $contents_updated = ScheduledEntities::getScheduledAClassContentUpdates(self::$_em, true, true);
        $contents_deleted = ScheduledEntities::getSchedulesAClassContentDeletions(self::$_em, true);

        // Updates content-content indexes
        self::_updateIdxContentContents(array_merge($contents_inserted, $contents_updated), $contents_deleted);

        // Updates site-content indexes
        self::_updateIdxSiteContents($contents_inserted, $contents_deleted);


        $content = $event->getTarget();
        if (!($content instanceof AClassContent))
            return;
        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();
        if (NULL === $application)
            return;

        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();

        if ($uow->isScheduledForInsert($content) || $uow->isScheduledForUpdate($content)) {
            self::$_em->getRepository('BackBuilder\ClassContent\Indexes\OptContentByModified')
                    ->replaceOptContentTable($content);

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
            self::$_em->getRepository('BackBuilder\ClassContent\Indexes\OptContentByModified')
                    ->removeOptContentTable($content);

            foreach ($content->getIndexation() as $index) {
                $em->remove($index);
                $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata('BackBuilder\ClassContent\Indexation'), $index);
            }
        }
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            'classcontent.onflush' => 'onFlushContent',
            'nestednode.page.onflush' => 'onFlushPage'
        );
    }

}
