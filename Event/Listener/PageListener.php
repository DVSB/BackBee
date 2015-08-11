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

use Doctrine\ORM\Event\PreUpdateEventArgs;

use BackBee\BBApplication;
use BackBee\Event\Event;
use BackBee\NestedNode\Page;
use BackBee\NestedNode\Repository\PageRepository;
use BackBee\NestedNode\Section;

/**
 * Page events listener.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class PageListener
{
    /**
     * @var BackBee\BBApplication
     */
    protected $_application;

    public function __construct(BBApplication $application)
    {
        $this->_application = $application;
    }

    /**
     * @param \BackBee\Event\Event $event
     */
    public function onPostLoad(Event $event)
    {
        $page = $event->getTarget();

        if (!($page instanceof Page)) {
            return;
        }

        $isBbSessionActive = $this->_application->getBBUserToken() === null;

        $page->setUseUrlRedirect($isBbSessionActive);
    }

    public static function setSectionHasChildren($em, Section $section = null)
    {
        if ($section !== null) {
            $repo = $em->getRepository('BackBee\NestedNode\Page');
            $notDeletedDescendants = $repo->getNotDeletedDescendants($section->getPage(), 1, false, [], true, 0, 1);

            //var_dump($section->getUid().' '.(string)isset($notDeletedDescendants->getIterator()[0]));

            $section->setHasChildren(isset($notDeletedDescendants->getIterator()[0]));
            $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata('BackBee\NestedNode\Section'), $section);
        }
    }

    /**
     * Occur on nestednode.page.preupdate events and nestednode.section.preupdate.
     *
     * @access public
     *
     * @param Event $event
     */
    public static function onPreUpdate(Event $event)
    {
        $page = $event->getTarget();
        $eventArgs = $event->getEventArgs();
        $updateParents = false;
        $new = $old = null;

        if ($eventArgs instanceof PreUpdateEventArgs) {
            if ($page instanceof Page && $eventArgs->hasChangedField('_section')) {
                var_dump('update page section');
                $old = $eventArgs->getOldValue('_section');
                $new = $eventArgs->getNewValue('_section');

                if ($new->getUid() === $page->getUid()) {
                    return;
                }
                $updateParents = true;
            }

            if ($page instanceof Page && $eventArgs->hasChangedField('_state')) {
                var_dump('update page state');
                if ($page->getParent() !== null) {
                    $new = $page->getParent()->getSection();
                    $updateParents = true;
                }
            }

            if ($page instanceof Section && $eventArgs->hasChangedField('_parent')) {
                var_dump('update section parent');
                $old = $eventArgs->getOldValue('_parent');
                $new = $eventArgs->getNewValue('_parent');
                $updateParents = true;
            }

            if ($updateParents) {
                $em = $event->getApplication()->getEntityManager();

                self::setSectionHasChildren($em, $old);
                self::setSectionHasChildren($em, $new);
            }
        }
    }

    /**
     * Occur on nestednode.page.preupdate events and nestednode.section.preupdate.
     *
     * @access public
     *
     * @param Event $event
     */
    public static function onPrePersist(Event $event)
    {
        $em = $event->getApplication()->getEntityManager();
        $uow = $em->getUnitOfWork();
        $page = $event->getTarget();

        if ($uow->isScheduledForInsert($page) && $page->getParent() !== null) {
            var_dump('pre persist');
            self::setSectionHasChildren($em, $page->getParent()->getSection());
        }
    }
}
