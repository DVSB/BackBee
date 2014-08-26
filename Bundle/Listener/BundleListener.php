<?php
namespace BackBuilder\Bundle\Listener;

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

use BackBuilder\Bundle\BundleInterface;
use BackBuilder\Event\Event;

/**
 *
 *
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class BundleListener
{

    /**
     * [onApplicationStart description]
     *
     * @param  Event  $event [description]
     */
    public static function onApplicationStart(Event $event)
    {
        $application = $event->getTarget();
        if (false === $application->isStarted()) {
            return;
        }

        $application->getContainer()->get('bundle.loader')->loadBundlesRoutes();
    }

    /**
     * [onGetBundleService description]
     *
     * @param  Event  $event [description]
     */
    public static function onGetBundleService(Event $event)
    {
        $bundle = $event->getTarget();
        if (false === $bundle->isStarted()) {
            $bundle->start();
            $bundle->started();

            $definition = $event->getApplication()->getContainer()->getDefinition($event->getArgument('id'));
            $definition->addTag('bundle.started', array('dispatch_event' => false));
        }
    }

    /**
     * [onApplicationStop description]
     *
     * @param  Event  $event [description]
     */
    public static function onApplicationStop(Event $event)
    {
        $application = $event->getTarget();
        foreach (array_keys($application->getContainer()->findTaggedServiceIds('bundle.started')) as $bundle_id) {
            $bundle = $application->getContainer()->get($bundle_id);
            if (true === ($bundle instanceof BundleInterface) && true === $bundle->isStarted()) {
                $bundle->stop();
            }
        }
    }
}
