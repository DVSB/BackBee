<?php

namespace BackBuilder\Bundle\Listener;

use BackBuilder\Bundle\BundleLoader;
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

        $container = $application->getContainer();
        $controller = $container->get('controller');
        foreach ($container->get('registry')->get('bundle.config_services_id', array()) as $service_id) {
            $config = $container->get($service_id);
            $recipe = BundleLoader::getBundleLoaderRecipeFor($config, BundleLoader::ROUTE_RECIPE_KEY);
            if (null === $recipe) {
                $route = $config->getRouteConfig();
                if (false === is_array($route) || 0 === count($route)) {
                    continue;
                }

                $controller->registerRoutes(str_replace('.config', '', $service_id), $route);
            } else {
                if (true === is_callable($recipe)) {
                    call_user_func_array($recipe, array($application, $config));
                }
            }
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
