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
    BackBuilder\Util\Doctrine\ScheduledEntities,
    BackBuilder\Event\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface,
    Symfony\Component\Security\Core\Util\ClassUtils;

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
     * Is the deletion of cached page is done
     * @var boolean
     */
    private static $_page_cache_deletion_done = false;

    /**
     * Is the deletion of cached content is done
     * @var boolean
     */
    private static $_content_cache_deletion_done = false;

    /**
     * Looks for available cached data before rendering a content
     * @param \BackBuilder\Event\Event $event
     */
    public static function onPreRenderContent(Event $event)
    {
        // Checks if a renderer is available
        $renderer = $event->getEventArgs();
        if (false === ($renderer instanceof \BackBuilder\Renderer\ARenderer)) {
            return;
        }

        // Checks if content caching is available
        if (false === self::_checkCacheContentEvent($event)) {
            return;
        }

        // Checks if cache data is available
        $cache_id = self::_getContentCacheId($renderer);
        if (false === $data = self::$_cache_content->load($cache_id)) {
            return;
        }

        $renderer->setRender($data);
        $event->getDispatcher()->dispatch('cache.postrender', new Event(self::$_object, array($renderer, $data)));
        self::$_application->debug(sprintf('Found cache for rendering `%s(%s)` with mode `%s`.', get_class(self::$_object), self::$_object->getUid(), $renderer->getMode()));
    }

    /**
     * Saves in cache the rendered cache data
     * @param \BackBuilder\Event\Event $event
     */
    public static function onPostRenderContent(Event $event)
    {
        // Checks if a renderer is available
        $args = $event->getEventArgs();
        $renderer = array_shift($args);
        if (false === ($renderer instanceof \BackBuilder\Renderer\ARenderer)) {
            return;
        }

        // Checks if content caching is available
        if (false === self::_checkCacheContentEvent($event)) {
            return;
        }

        // Checks if cache_id is available
        if (false === $cache_id = self::_getContentCacheId($renderer)) {
            return;
        }

        // Gets the lifetime to set
        if (null === $lifetime = self::$_object->getProperty('cache-lifetime')) {
            $lifetime = 0;
        }

        // Computes $lifetime according param and children
        $uids = self::$_application->getEntityManager()
                ->getRepository(ClassUtils::getRealClass(self::$_object))
                ->getUnorderedChildrenUids(self::$_object);
        $lifetime = self::$_cache_content->getMinExpireByTag($uids, $lifetime);

        $render = array_shift($args);
        self::$_cache_content->save($cache_id, $render, $lifetime, self::$_object->getUid());
        self::$_application->debug(sprintf('Save cache for rendering `%s(%s)` with mode `%s`.', get_class(self::$_object), self::$_object->getUid(), $renderer->getMode()));
    }

    /**
     * Clears cached data associated to the content to be flushed
     * @param \BackBuilder\Event\Event $event
     */
    public static function onFlushContent(Event $event)
    {
        // Checks if page caching is available
        if (false === self::_checkCacheContentEvent($event, false)) {
            return;
        }

        if (true === self::$_content_cache_deletion_done) {
            return;
        }

        $contents = ScheduledEntities::getScheduledAClassContentUpdates(self::$_application->getEntityManager());
        if (0 === count($contents)) {
            return;
        }

        $content_uids = array();
        foreach ($contents as $content) {
            $content_uids[] = $content->getUid();
        }

        self::$_cache_content->removeByTag($content_uids);
        self::$_content_cache_deletion_done = true;
        self::$_application->debug(sprintf('Remove cache for `%s(%s)`.', get_class(self::$_object), implode(', ', $content_uids)));
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

        // Checks if cache data is available
        $cache_id = self::_getPageCacheId();
        if (false === $data = self::$_cache_page->load($cache_id)) {
            return;
        }

        $renderer->setRender($data);
        $event->getDispatcher()->dispatch('cache.postrender', new Event(self::$_object, array($renderer, $data)));
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

        // Checks if cache_id is available
        if (false === $cache_id = self::_getPageCacheId()) {
            return;
        }

        $lifetime = 0; // @todo: compute lifetime
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
        if (false === self::_checkCachePageEvent($event, false)) {
            return;
        }

        if (true === self::$_page_cache_deletion_done) {
            return;
        }

        $pages = ScheduledEntities::getScheduledEntityUpdatesByClassname(self::$_application->getEntityManager(), 'BackBuilder\NestedNode\Page');
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
     * Checks the event and system validity then returns the content target, FALSE otherwise
     * @param \BackBuilder\Event\Event $event
     * @param boolean $check_status
     * @return boolean
     */
    private static function _checkCacheContentEvent(Event $event, $check_status = true)
    {
        // Checks if the target event is a content
        self::$_object = $event->getTarget();
        if (false === (self::$_object instanceof \BackBuilder\ClassContent\AClassContent)) {
            return false;
        }

        // Checks if the target event is not a main contentset
        if (self::$_object instanceof \BackBuilder\ClassContent\ContentSet &&
                true === is_array(self::$_object->getPages()) &&
                0 < self::$_object->getPages()->count()) {
            return false;
        }

        // Checks if a service cache-control exists
        self::$_application = $event->getDispatcher()->getApplication();
        if (null === self::$_application || false === self::$_application->getContainer()->has('cache-control')) {
            return false;
        }

        // Checks if the service cache-control extends AExtendedCache
        self::$_cache_content = self::$_application->getContainer()->get('cache-control');
        if (false === (self::$_cache_content instanceof \BackBuilder\Cache\AExtendedCache)) {
            return false;
        }

        return (true === $check_status) ? self::_checkCacheStatus() : true;
    }

    /**
     * Checks the event and system validity then returns the page target, FALSE otherwise
     * @param \BackBuilder\Event\Event $event
     * @param boolean $check_status
     * @return boolean
     */
    private static function _checkCachePageEvent(Event $event, $check_status = true)
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

        return (true === $check_status) ? self::_checkCacheStatus() : true;
    }

    /**
     * Return FALSE if debug mode is activated or a BBUser is connected
     * @return boolean
     */
    private static function _checkCacheStatus()
    {
        if (true === self::$_application->isStarted() && false === self::$_application->isClientSAPI()) {
            $request = self::$_application->getRequest();
            if ('GET' !== $request->getMethod()) {
                return false;
            }
        }

        if (true === self::$_application->isDebugMode()) {
            return false;
        }

        if (null !== $token = self::$_application->getBBUserToken()) {
            return false;
        }

        return true;
    }

    /**
     * Return the cache id for the current rendered content
     * @return string|FALSE
     */
    private static function _getContentCacheId(\BackBuilder\Renderer\ARenderer $renderer)
    {
        $cache_id = self::$_object->getUid() . '-' . $renderer->getMode();

        if (true === self::$_application->isStarted() && false === self::$_application->isClientSAPI()) {
            $request = self::$_application->getRequest();
            if ('GET' === $request->getMethod()) {
                $param = self::$_object->getProperty('cache-param');
                if (true === is_array($param) && true === array_key_exists('query', $param)) {
                    $query = (array) $param['query'];
                    foreach ($query as $q) {
                        if (null !== $value = $request->get(str_replace('#uid#', self::$_object->getUid(), $q))) {
                            $cache_id .= '-' . $q . '=' . $value;
                        }
                    }
                }
            }
        }

        return md5('_content_' . $cache_id);
    }

    /**
     * Return the cache id for the current requested page
     * @return string|FALSE
     */
    private static function _getPageCacheId()
    {
        if (false === self::$_application->isStarted() || true === self::$_application->isClientSAPI()) {
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
            'classcontent.prerender' => 'onPreRenderContent',
            'nestednode.page.prerender' => 'onPreRenderPage',
            'nestednode.page.postrender' => 'onPostRenderPage',
            'nestednode.page.onflush' => 'onFlushPage'
        );
    }

    /**
     * @deprecated since version 0.9
     */
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

    /**
     * @deprecated since version 0.9
     */
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

}
