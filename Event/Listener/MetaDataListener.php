<?php

namespace BackBuilder\Event\Listener;

use BackBuilder\Event\Event,
    BackBuilder\NestedNode\Page,
    BackBuilder\ClassContent\AClassContent;

/**
 * Listener to metadata events
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event\Listener
 * @copyright   Lp system
 * @author      c.rouillon
 */
class MetaDataListener
{

    /**
     * Occur on classcontent.onflush events
     * @param \BackBuilder\Event\Event $event
     */
    public static function onFlushContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AClassContent))
            return;

        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();
        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();
        if ($uow->isScheduledForDelete($content))
            return;

        if (null !== $page = $content->getMainNode()) {
            $newEvent = new Event($page, $content);
            $newEvent->setDispatcher($event->getDispatcher());
            self::onFlushPage($newEvent);
        }
//
//        foreach($content->getParentContent() as $parent) {
//            if (null !== $page = $parent->getMainNode()) {
//                $newEvent = new Event($page, $parent);
//                $newEvent->setDispatcher($event->getDispatcher());
//                self::onFlushPage($newEvent);
//            }
//
//            $newEvent = new Event($parent);
//            $newEvent->setDispatcher($event->getDispatcher());
//            self::onFlushContent($newEvent);
//        }
    }

    /**
     * Occur on nestednode.page.onflush events
     * @param \BackBuilder\Event\Event $event
     */
    public static function onFlushPage(Event $event)
    {
        $page = $event->getTarget();
        if (!($page instanceof Page))
            return;

        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();
        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();
        if ($uow->isScheduledForDelete($page))
            return;

        if (null === $metadata = $page->getMetaData()) {
            if (null === $metadata_config = $application->getTheme()->getConfig()->getSection('metadata')) {
                $metadata_config = $application->getConfig()->getSection('metadata');
            }

            $metadata = new \BackBuilder\MetaData\MetaDataBag($metadata_config, $page);
        }
        $page->setMetaData($metadata->compute($page));

        if ($uow->isScheduledForInsert($page) || $uow->isScheduledForUpdate($page))
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata('BackBuilder\NestedNode\Page'), $page);
        elseif (!$uow->isScheduledForDelete($page))
            $uow->computeChangeSet($em->getClassMetadata('BackBuilder\NestedNode\Page'), $page);
    }

}