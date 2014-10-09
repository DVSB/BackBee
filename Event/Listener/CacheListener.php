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

use BackBuilder\IApplication as ApplicationInterface;
use BackBuilder\Cache\AExtendedCache;
use BackBuilder\Cache\CacheIdentifierGenerator;
use BackBuilder\Cache\CacheValidator;
use BackBuilder\ClassContent\AClassContent;
use BackBuilder\ClassContent\ContentSet;
use BackBuilder\Event\Event;
use BackBuilder\NestedNode\Page;
use BackBuilder\Renderer\ARenderer;
use BackBuilder\Renderer\Event\RendererEvent;
use BackBuilder\Util\Doctrine\ScheduledEntities;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Listener to Cache events
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>, e.chau <eric.chau@lp-digital.fr>
 */
class CacheListener implements EventSubscriberInterface
{
    /**
     * The current application instance
     *
     * @var \BackBuilder\BBApplication
     */
    private $application;

    /**
     * cache validator
     *
     * @var BackBuilder\Cache\CacheValidator
     */
    private $validator;

    /**
     * cache identifier generator
     *
     * @var BackBuilder\Cache\CacheIdentifierGenerator
     */
    private $identifier_generator;

    /**
     * The page cache system
     *
     * @var \BackBuilder\Cache\AExtendedCache
     */
    private $cache_page;

    /**
     * The content cache system
     *
     * @var \BackBuilder\Cache\AExtendedCache
     */
    private $cache_content;

    /**
     * The object to be rendered
     *
     * @var \BackBuilder\Renderer\IRenderable
     */
    private $object;

    /**
     * Is the deletion of cached page is done
     *
     * @var boolean
     */
    private $page_cache_deletion_done = false;

    /**
     * Cached contents already deleted
     *
     * @var boolean
     */
    private $content_cache_deletion_done = array();

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            'classcontent.prerender'     => 'onPreRenderContent',
            'classcontent.postrender'    => 'onPostRenderContent',
            'classcontent.onflush'       => 'onFlushContent',
            'nestednode.page.prerender'  => 'onPreRenderPage',
            'nestednode.page.postrender' => 'onPostRenderPage',
            'nestednode.page.onflush'    => 'onFlushPage'
        );
    }

    /**
     * constructor
     *
     * @param ApplicationInterface     $application
     * @param CacheValidator           $validator
     * @param CacheIdentifierGenerator $generator
     */
    public function __construct(ApplicationInterface $application, CacheValidator $validator, CacheIdentifierGenerator $generator)
    {
        $this->application = $application;
        $this->validator = $validator;
        $this->identifier_generator = $generator;

        if (true === $this->application->getContainer()->has('cache.control')) {
            $cache_content = $this->application->getContainer()->get('cache.control');
            if (true === ($cache_content instanceof AExtendedCache)) {
                $this->cache_content = $cache_content;
            }
        }

        if (true === $this->application->getContainer()->has('cache.page')) {
            $cache_page = $this->application->getContainer()->get('cache.page');
            if (true === ($cache_page instanceof AExtendedCache)) {
                $this->cache_page = $cache_page;
            }
        }
    }

    /**
     * Looks for available cached data before rendering a content
     * @param \BackBuilder\Event\Event $event
     */
    public function onPreRenderContent(RendererEvent $event)
    {
        // Checks if content caching is available
        $this->object = $event->getTarget();

        if (false === ($this->object instanceof AClassContent) || false === $this->checkCacheContentEvent()) {
            return;
        }

        $renderer = $event->getRenderer();
        // Checks if cache data is available
        $cache_id = $this->getContentCacheId($renderer);
        if (false === $data = $this->cache_content->load($cache_id)) {
            return;
        }

        $renderer->setRender($data);
        $event->getDispatcher()->dispatch('cache.postrender', new Event($this->object, array($renderer, $data)));
        $this->application->debug(sprintf(
            'Found cache (id: %s) for rendering `%s(%s)` with mode `%s`.',
            $cache_id,
            get_class($this->object),
            $this->object->getUid(),
            $renderer->getMode()
        ));
    }

    /**
     * Saves in cache the rendered cache data
     * @param \BackBuilder\Event\Event $event
     */
    public function onPostRenderContent(RendererEvent $event)
    {
        // Checks if content caching is available
        $this->object = $event->getTarget();
        if (false === ($this->object instanceof AClassContent) || false === $this->checkCacheContentEvent()) {
            return;
        }

        $renderer = $event->getRenderer();
        // Checks if cache_id is available
        if (false === $cache_id = $this->getContentCacheId($renderer)) {
            return;
        }

        // Gets the lifetime to set
        if (null === $lifetime = $this->object->getProperty('cache-lifetime')) {
            $lifetime = 0;
        }

        // Computes $lifetime according param and children
        $uids = $this->application->getEntityManager()->getRepository(ClassUtils::getRealClass($this->object))
            ->getUnorderedChildrenUids($this->object)
        ;

        $lifetime = $this->cache_content->getMinExpireByTag($uids, $lifetime);

        $render = $event->getRender();
        $this->cache_content->save($cache_id, $render, $lifetime, $this->object->getUid());
        $this->application->debug(sprintf(
            'Save cache (id: %s, lifetime: %d) for rendering `%s(%s)` with mode `%s`.',
            $cache_id,
            $lifetime,
            get_class($this->object),
            $this->object->getUid(),
            $renderer->getMode()
        ));
    }

    /**
     * Clears cached data associated to the content to be flushed
     * @param \BackBuilder\Event\Event $event
     */
    public function onFlushContent(Event $event)
    {
        // Checks if page caching is available
        $this->object = $event->getTarget();
        if (false === ($this->object instanceof AClassContent) || false === $this->checkCacheContentEvent(false)) {
            return;
        }

        $parent_uids = $this->application->getEntityManager()
            ->getRepository('BackBuilder\ClassContent\Indexes\IdxContentContent')
            ->getParentContentUids(array($this->object))
        ;

        $content_uids = array_diff($parent_uids, $this->content_cache_deletion_done);
        if (0 === count($content_uids)) {
            return;
        }

        $this->cache_content->removeByTag($content_uids);
        $this->content_cache_deletion_done = array_merge($this->content_cache_deletion_done, $content_uids);
        $this->application->debug(sprintf(
            'Remove cache for `%s(%s)`.',
            get_class($this->object),
            implode(', ', $content_uids)
        ));

        if (false === $this->application->getContainer()->has('cache.page')) {
            return;
        }

        $cache_page = $this->application->getContainer()->get('cache.page');
        if (true === ($cache_page instanceof AExtendedCache)) {
            $node_uids = $this->application->getEntityManager()
                ->getRepository('BackBuilder\ClassContent\Indexes\IdxContentContent')
                ->getNodeUids($content_uids)
            ;
            $cache_page->removeByTag($node_uids);
            $this->application->debug(sprintf('Remove cache for page %s.', implode(', ', $node_uids)));
        }
    }

    /**
     * Looks for available cached data before rendering a page
     * @param \BackBuilder\Event\Event $event
     */
    public function onPreRenderPage(RendererEvent $event)
    {
        // Checks if page caching is available
        $this->object = $event->getTarget();

        if (false === ($this->object instanceof Page) || false === $this->checkCachePageEvent()) {
            return;
        }

        // Checks if cache data is available
        $cache_id = $this->getPageCacheId();
        if (false === $data = $this->cache_page->load($cache_id)) {
            return;
        }

        $renderer = $event->getRenderer();
        $renderer->setRender($data);
        $event->getDispatcher()->dispatch('cache.postrender', new Event($this->object, array($renderer, $data)));
        $this->application->debug(sprintf(
            'Found cache (id: %s) for rendering `%s(%s)` with mode `%s`.',
            $cache_id,
            get_class($this->object),
            $this->object->getUid(),
            $renderer->getMode()
        ));
    }

    /**
     * Saves in cache the rendered page data
     * @param \BackBuilder\Event\Event $event
     */
    public function onPostRenderPage(RendererEvent $event)
    {
        // Checks if page caching is available
        $this->object = $event->getTarget();
        if (false === ($this->object instanceof Page) || false === $this->checkCachePageEvent()) {
            return;
        }

        // Checks if cache_id is available
        if (false === $cache_id = $this->getPageCacheId()) {
            return;
        }

        $column_uids = array();
        foreach ($this->object->getContentSet() as $column) {
            if ($column instanceof AClassContent) {
                $column_uids[] = $column->getUid();
            }
        }

        $lifetime = $this->cache_page->getMinExpireByTag($column_uids);
        $render = array_shift($args);
        $this->cache_page->save($cache_id, $render, $lifetime, $this->object->getUid());
        $this->application->debug(sprintf(
            'Save cache (id: %s, lifetime: %d) for rendering `%s(%s)` with mode `%s`.',
            $cache_id,
            $lifetime,
            get_class($this->object),
            $this->object->getUid(),
            $event->getRenderer()->getMode()
        ));
    }

    /**
     * Clears cached data associated to the page to be flushed
     * @param \BackBuilder\Event\Event $event
     */
    public function onFlushPage(Event $event)
    {
        // Checks if page caching is available
        $this->object = $event->getTarget();
        if (false === ($this->object instanceof Page) || false === $this->checkCachePageEvent(false)) {
            return;
        }

        if (true === $this->page_cache_deletion_done) {
            return;
        }

        $pages = ScheduledEntities::getScheduledEntityUpdatesByClassname(
            $this->application->getEntityManager(), 'BackBuilder\NestedNode\Page'
        );
        if (0 === count($pages)) {
            return;
        }

        $page_uids = array();
        foreach ($pages as $page) {
            $page_uids[] = $page->getUid();
        }

        $this->cache_page->removeByTag($page_uids);
        $this->page_cache_deletion_done = true;
        $this->application->debug(sprintf(
            'Remove cache for `%s(%s)`.',
            get_class($this->object),
            implode(', ', $page_uids)
        ));
    }

    /**
     * Checks the event and system validity then returns the content target, FALSE otherwise
     * @param \BackBuilder\Event\Event $event
     * @param boolean $check_status
     * @return boolean
     */
    private function checkCacheContentEvent($check_status = true)
    {
        // Checks if a service cache-control exists
        if (null === $this->cache_content) {
            return false;
        }

        // Checks if the target event is not a main contentset
        if (
            $this->object instanceof ContentSet
            && true === is_array($this->object->getPages())
            && 0 < $this->object->getPages()->count()
        ) {
            return false;
        }

        return true === $check_status ? $this->validator->isValid('cache_status', $this->object) : true;
    }

    /**
     * Checks the event and system validity then returns the page target, FALSE otherwise
     * @param \BackBuilder\Event\Event $event
     * @param boolean $check_status
     * @return boolean
     */
    private function checkCachePageEvent($check_status = true)
    {
        return null !== $this->cache_page
            && true === $this->validator->isValid('page', $this->application->getRequest()->getUri())
            && (
                true === $check_status
                    ? $this->validator->isValid('cache_status', $this->object)
                    : true
            )
        ;
    }

    /**
     * Return the cache id for the current rendered content
     * @return string|FALSE
     */
    private function getContentCacheId(ARenderer $renderer)
    {
        $cache_id = $this->identifier_generator->compute(
            'content', $this->object->getUid() . '-' . $renderer->getMode(),
            $renderer
        );

        return md5('_content_' . $cache_id);
    }

    /**
     * Return the cache id for the current requested page
     * @return string|FALSE
     */
    private function getPageCacheId()
    {
        return $this->application->getRequest()->getUri();
    }
}
