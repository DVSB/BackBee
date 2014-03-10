<?php

namespace BackBuilder\Event\Listener;

use BackBuilder\Event\Event;

/**
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class BundleListener
{

    public static function onApplicationStart(Event $event)
    {
        $application = $event->getTarget();
        if (
            null === $application 
            || false === is_a($application, 'BackBuilder\BBApplication')
            || false === $application->isStarted()
        ) {
            return;
        }

        $bundleConfigs = $application->getContainer()->get('registry')->get('bundles.baseconfig', array());
        $controller = $application->getContainer()->get('controller');
        foreach ($bundleConfigs as $key => $config) {
            $route = $config->getRouteConfig();
            if (false === is_array($route) || 0 === count($route)) {
                continue;
            }

            $controller->registerRoutes($key, $route);
        }
    }

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
        foreach ($application->getContainer()->get('registry')->get('bundles.started', array()) as $bundle) {
            if (true === is_object($bundle) && true === is_a($bundle, 'BackBuilder\Bundle\ABundle')) {
                $bundle->stop();
            }
        }
    }
}
