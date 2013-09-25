<?php

namespace BackBuilder\Event\Listener;

use BackBuilder\ClassContent\AClassContent,
    BackBuilder\Event\Event;

class CacheListener
{

    public static function onPreRender(Event $event)
    {
        $dispatcher = $event->getDispatcher();
        if (NULL === $application = $dispatcher->getApplication())
            return;
        if (NULL !== $token = $application->getBBUserToken())
            return;

        $content = $event->getTarget();
        if (!is_a($content, 'BackBuilder\ClassContent\AClassContent'))
            return;
        if ($content->isElementContent())
            return;

        if (0 !== $application->getController()->getRequest()->request->count())
            return;

        if (NULL === $lifetime = $content->getProperty('cache-lifetime'))
            $lifetime = 0;
        if (0 > $lifetime)
            return;

        $renderer = $event->getEventArgs();
        if (!is_a($renderer, 'BackBuilder\Renderer\ARenderer'))
            return;

        $unencrypted_uid = $content->getUid() . '-' . $renderer->getMode();
        if (NULL !== $param = $content->getProperty('cache-param')) {
            if (array_key_exists('query', $param)) {
                $query = (array) $param['query'];
                foreach ($query as $q) {
                    if (NULL !== $value = $application->getRequest()->get(str_replace('#uid#', $content->getUid(), $q))) {
                        $unencrypted_uid .= '-' . $q . '=' . $value;
                    }
                }
            }
        }

        $cache_uid = md5($unencrypted_uid);
//        $cache_uid = md5($content->getUid().'-'.$renderer->getMode());
        if (false !== $data = $application->getCacheControl()->load($cache_uid)) {
            $application->debug(sprintf('Found cache for rendering `%s(%s)` with mode `%s`.', get_class($content), $content->getUid(), $renderer->getMode()));
            $renderer->setRender($data);
            $dispatcher->dispatch('cache.postrender', new Event($content, array($renderer, $data)));
        }
    }

    public static function onPostRender(Event $event)
    {

        $dispatcher = $event->getDispatcher();
        if (NULL === $application = $dispatcher->getApplication())
            return;
        if (NULL !== $token = $application->getBBUserToken())
            return;

        $content = $event->getTarget();
        if (!is_a($content, 'BackBuilder\ClassContent\AClassContent'))
            return;
        if ($content->isElementContent())
            return;

        if (0 !== $application->getController()->getRequest()->request->count())
            return;

        $args = $event->getEventArgs();
        $renderer = array_shift($args);
        if (!is_a($renderer, 'BackBuilder\Renderer\ARenderer'))
            return;

        if (NULL === $lifetime = $content->getProperty('cache-lifetime'))
            $lifetime = 0;
        if (0 > $lifetime)
            return;

        foreach ($content->getData() as $subcontents) {
            if (!is_array($subcontents))
                $subcontents = array($subcontents);
            foreach ($subcontents as $subcontent) {
                if ($subcontent instanceof AClassContent) {
                    if (NULL !== $subcontent->getProperty('cache-param')) {
                        // some cache params exist for subcontent, forget cache for this content
                        return;
                    }

                    if ((NULL !== $sublifetime = $subcontent->getProperty('cache-lifetime')) && (0 !== $sublifetime)) {
                        $lifetime = (0 === $lifetime) ? $sublifetime : min(array($sublifetime, $lifetime));
                    }
                }
            }
        }
        $render = array_shift($args);
        //$cache_uid = md5($content->getUid().'-'.$renderer->getMode());

        $unencrypted_uid = $content->getUid() . '-' . $renderer->getMode();
        if (NULL !== $param = $content->getProperty('cache-param')) {
            if (array_key_exists('query', $param)) {
                $query = (array) $param['query'];
                foreach ($query as $q) {
                    if (NULL !== $value = $application->getRequest()->get(str_replace('#uid#', $content->getUid(), $q))) {
                        $unencrypted_uid .= '-' . $q . '=' . $value;
                    }
                }
            }
        }
        $cache_uid = md5($unencrypted_uid);
        $application->getCacheControl()->save($cache_uid, $render, $lifetime, $content->getUid());
        $application->debug(sprintf('Save cache for rendering `%s(%s)` with mode `%s`.', get_class($content), $content->getUid(), $renderer->getMode()));
    }

    public static function onFlushContent(Event $event)
    {
        $dispatcher = $event->getDispatcher();
        if (NULL === $application = $dispatcher->getApplication())
            return;

        $content = $event->getTarget();
        if (!is_a($content, 'BackBuilder\ClassContent\AClassContent'))
            return;
        if ($content->isElementContent())
            return;

        if (false === is_a($application->getCacheControl(), 'BackBuilder\Cache\AExtendedCache')) {
            return;
        }

        $application->getCacheControl()->removeByTag(array($content->getUid()));

        if ($parentUids = $application->getEntityManager()->getrepository(get_class($content))->getParentContentUid($content)) {
            $application->getCacheControl()->removeByTag($parentUids);
        }
    }

}