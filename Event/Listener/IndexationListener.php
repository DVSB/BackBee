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
    BackBuilder\ClassContent\AClassContent;

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

    /**
     * Subscriber to nestednode.page.onflush event
     *     - Replace page-content and site-content indexes if 
     *       the Page target is inserted or updated
     *     - Remove page-content ad site-content if the Page target is deleted
     * @param \BackBuilder\Event\Event $event
     */
    public static function onFlushPage(Event $event)
    {
        // @todo
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

        if ($uow->isScheduledForInsert($content) || $uow->isScheduledForUpdate($content)) {
            if (null !== $site = $application->getSite()) {
                if (null !== $content->getMainNode()) {
                    $em->getRepository('BackBuilder\ClassContent\Indexation')
                            ->updateIdxSiteContent($site, $content);
                }
            }


            if (is_array($content->getProperty()) && array_key_exists('indexation', $content->getProperty())) {
                foreach ($content->getProperty('indexation') as $indexedElement) {
                    $indexedElement = (array) $indexedElement;
                    $elements = explode('->', $indexedElement[0]);
                    $callback = array_key_exists(1, $indexedElement) ? $indexedElement[1] : NULL;

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