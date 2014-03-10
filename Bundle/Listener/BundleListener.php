<?php

namespace BackBuilder\Event\Listener;

use BackBuilder\Event\Event;

/**
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class BundleListener
{
    /**
     * [onGetBundleService description]
     * @param  Event  $event [description]
     * @return [type]        [description]
     */
    public static function onGetBundleService(Event $event)
    {
        $bundle = $event->getTarget();
        if (true === is_a($bundle, 'BackBuilder\Bundle\ABundle') && false === $bundle->isStarted()) {
            $bundle->start();
            $bundle->started(); // put bundle as started

            // add this bundle to started bundle so we can stop them at bbapplication.stop event
            $registry = $event->getApplication()->getContainer()->get('registry');
            $startedBundles = $registry->get('bundles.started', array());
            $startedBundles[] = $bundle;
            $registry->set('bundles.started', $startedBundles);
        }
    }

    /**
     * [onApplicationStop description]
     * @param  Event  $event [description]
     * @return [type]        [description]
     */
    public static function onApplicationStop(Event $event)
    {
        $application = $event->getTarget();
        foreach ($application->getContainer()->get('registry')->get('bundles.started') as $bundle) {
            if (true === is_object($bundle) && true === is_a($bundle, 'BackBuilder\Bundle\ABundle')) {
                $bundle->stop();
            }
        }
    }
}
