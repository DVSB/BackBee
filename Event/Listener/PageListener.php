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

use BackBuilder\Event\Event;
use BackBuilder\BBApplication;
use BackBuilder\NestedNode\Page;

/**
 * Page events listener
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class PageListener
{
    /**
     *
     * @var BackBuilder\BBApplication
     */
    protected $_application;

    public function __construct(BBApplication $application)
    {
        $this->_application = $application;
    }

    /**
     *
     * @param \BackBuilder\Event\Event $event
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
}
