<?php
namespace BackBuilder\Renderer\Listener;

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

use BackBuilder\Event\Event;

/**
 * Twig renderer adapter listener
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class TwigListener
{
    /**
     * occurs on `bbapplication.start`
     *
     * @param  Event  $event
     */
    public static function onApplicationStart(Event $event)
    {
        $application = $event->getTarget();

        $twig_adapter = $application->getRenderer()->getAdapter('twig');
        foreach ($application->getContainer()->findTaggedServiceIds('twig.extension') as $id => $datas) {
            $twig_adapter->addExtension($application->getContainer()->get($id));
        }
    }
}