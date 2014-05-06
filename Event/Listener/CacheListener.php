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

use BackBuilder\ClassContent\AClassContent,
    BackBuilder\Event\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener to Cache events
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class CacheListener implements EventSubscriberInterface
{

    /**
     * The current application instance
     * @var \BackBuilder\BBApplication
     */
    private static $_application;

    /**
     * The page cache system
     * @var \BackBuilder\Cache\AExtendedCache
     */
    private static $_cache_page;

    /**
     * The content cache system
     * @var \BackBuilder\Cache\AExtendedCache
     */
    private static $_cache_content;

    /**
     * The object to be rendered
     * @var \BackBuilder\Renderer\IRenderable
     */
    private static $_object;

    /**
     * Is the deletion of ached page is done
     * @var boolean
     */
    private static $_page_cache_deletion_done = false;

    public static function onPreRender(Event $event)
    {
        $dispatcher = $event->getDispatcher();
        if (NULL === $application = $dispatcher->getApplication())
            return;
        if (NULL !== $token = $application->getBBUserToken())
            return;
        if (true === $application->debugMode())
            return;

        $content = $event->getTarget();
        if (!is_a($content, 'BackBuilder\ClassContent\AClassContent'))
            return;
//        if ($content->isElementContent())
//            return;

        if (0 !== $application->getController()->getRequest()->request->count())
            return;

        if (NULL === $lifetime = $content->getProperty('cache-lifetime')) {
            $lifetime = 0;
        }

        $application->debug('Cache lifetime defined : ' . $lifetime);

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
        if (true === $application->isDebugMode())
            return;

        $content = $event->getTarget();
        if (!is_a($content, 'BackBuilder\ClassContent\AClassContent'))
            return;
//        if ($content->isElementContent())
//            return;

        if (0 !== $application->getController()->getRequest()->request->count())
            return;

        $args = $event->getEventArgs();
        $renderer = array_shift($args);
        if (!is_a($renderer, 'BackBuilder\Renderer\ARenderer'))
            return;

        if (NULL === $lifetime = $content->getProperty('cache-lifetime')) {
            $lifetime = 0;
        }

        $application->debug('Cache lifetime defined : ' . $lifetime);

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
//        if ($content->isElementContent())
//            return;

        if (false === is_a($application->getCacheControl(), 'BackBuilder\Cache\AExtendedCache')) {
            return;
        }

        $application->getCacheControl()->removeByTag(array($content->getUid()));

        $parentUids = $application->getEntityManager()->getrepository(get_class($content))->getParentContentUid($content);
        $application->getCacheControl()->removeByTag($parentUids);
    }

    /**
     * Looks for available cached data before rendering a page
     * @param \BackBuilder\Event\Event $event
     */
    public static function onPreRenderPage(Event $event)
    {
        // Checks if a renderer is available
        $renderer = $event->getEventArgs();
        if (false === ($renderer instanceof \BackBuilder\Renderer\ARenderer)) {
            return;
        }

        // Checks if page caching is available
        if (false === self::_checkCachePageEvent($event)) {
            return;
        }

        // Checks the cache status
        if (false === self::_checkCacheStatus()) {
            return;
        }

        // Checks if CacheId is available
        if (false === $cache_id = self::_getPageCacheIdFromRequest()) {
            return;
        }

        // Checks if cache data is available
        if (false === $data = self::$_cache_page->load($cache_id)) {
            return;
        }

        $renderer->setRender($data);
        self::$_application->debug(sprintf('Found cache for rendering `%s(%s)` with mode `%s`.', get_class(self::$_object), self::$_object->getUid(), $renderer->getMode()));
    }

    /**
     * Saves in cache the rendered page data
     * @param \BackBuilder\Event\Event $event
     */
    public static function onPostRenderPage(Event $event)
    {
        // Checks if a renderer is available
        $args = $event->getEventArgs();
        $renderer = array_shift($args);
        if (false === ($renderer instanceof \BackBuilder\Renderer\ARenderer)) {
            return;
        }

        // Checks if page caching is available
        if (false === self::_checkCachePageEvent($event)) {
            return;
        }

        // Checks the cache status
        if (false === self::_checkCacheStatus()) {
            return;
        }

        // Checks if CacheId is available
        if (false === $cache_id = self::_getPageCacheIdFromRequest()) {
            return;
        }

        $lifetime = 0;
        $render = array_shift($args);
        self::$_cache_page->save($cache_id, $render, $lifetime, self::$_object->getUid());
        self::$_application->debug(sprintf('Save cache for rendering `%s(%s)` with mode `%s`.', get_class(self::$_object), self::$_object->getUid(), $renderer->getMode()));
    }

    /**
     * Clears cached data associated to the page to be flushed
     * @param \BackBuilder\Event\Event $event
     */
    public static function onFlushPage(Event $event)
    {
        // Checks if page caching is available
        if (false === self::_checkCachePageEvent($event)) {
            return;
        }

        if (true === self::$_page_cache_deletion_done) {
            return;
        }

        $pages = \BackBuilder\Util\Doctrine\ScheduledEntities::getScheduledEntityUpdatesByClassname(self::$_application->getEntityManager(), 'BackBuilder\NestedNode\Page');
        if (0 === count($pages)) {
            return;
        }

        $page_uids = array();
        foreach ($pages as $page) {
            $page_uids[] = $page->getUid();
        }

        self::$_cache_page->removeByTag($page_uids);
        self::$_page_cache_deletion_done = true;
        self::$_application->debug(sprintf('Remove cache for `%s(%s)`.', get_class(self::$_object), implode(', ', $page_uids)));
    }

    /**
     * Checks the event and system validity then returns the page target, FALSE otherwise
     * @param \BackBuilder\Event\Event $event
     * @return \BackBuilder\NestedNode\Page|boolean
     */
    private static function _checkCachePageEvent(Event $event)
    {
        // Checks if the target event is a Page
        self::$_object = $event->getTarget();
        if (false === (self::$_object instanceof \BackBuilder\NestedNode\Page)) {
            return false;
        }

        // Checks if a service cache.page exists
        self::$_application = $event->getDispatcher()->getApplication();
        if (null === self::$_application || false === self::$_application->getContainer()->has('cache.page')) {
            return false;
        }

        // Checks if the service cache.page extends AExtendedCache
        self::$_cache_page = self::$_application->getContainer()->get('cache.page');
        if (false === (self::$_cache_page instanceof \BackBuilder\Cache\AExtendedCache)) {
            return false;
        }

        return self::$_object;
    }

    /**
     * Return FALSE if debug mode is activated or a BBUser is connected
     * @return boolean
     */
    private static function _checkCacheStatus()
    {
        if (true === self::$_application->isDebugMode()) {
            return false;
        }

        if (null !== $token = self::$_application->getBBUserToken()) {
            return false;
        }

        return true;
    }

    /**
     * Return the cache id for the current requested page
     * @return string|FALSE
     */
    private static function _getPageCacheIdFromRequest()
    {

        if (null === self::$_application) {
            return false;
        }

        $request = self::$_application->getRequest();
        if ('GET' !== $request->getMethod()) {
            return false;
        }

        return $request->getUri();
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            'nestednode.page.prerender' => 'onPreRenderPage',
            'nestednode.page.postrender' => 'onPostRenderPage',
            'nestednode.page.onflush' => 'onFlushPage'
        );
    }

}
