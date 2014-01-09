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
    BackBuilder\ClassContent\ContentSet,
    BackBuilder\ClassContent\Revision,
    BackBuilder\Event\Event;

/**
 * Listener to ClassContent events :
 *    - classcontent.onflush: occurs when a classcontent entity is mentioned for current flush
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class RevisionListener
{

    public static function onRemoveContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AClassContent))
            return;

        $dispatcher = $event->getDispatcher();
        if (null === $dispatcher)
            return;

        $application = $dispatcher->getApplication();
        if (null === $application)
            return;

        $em = $application->getEntityManager();

        $revisions = $em->getRepository('BackBuilder\ClassContent\Revision')->getRevisions($content);
        foreach ($revisions as $revision) {
            $revision->setContent(NULL);
            $revision->setState(Revision::STATE_DELETED);
        }
    }

    public static function onFlushContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AClassContent))
            return;

        $dispatcher = $event->getDispatcher();
        if (null === $dispatcher)
            return;

        $application = $dispatcher->getApplication();
        if (null === $application)
            return;

        $token = $application->getSecurityContext()->getToken();
        if (null === $token)
            return;
        if ('BackBuilder\Security\Token\BBUserToken' != get_class($token))
            return;

        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();

        if ($uow->isScheduledForInsert($content) && AClassContent::STATE_NEW == $content->getState()) {
            $revision = $em->getRepository('BackBuilder\ClassContent\Revision')->checkout($content, $token);
            $em->persist($revision);
            $uow->computeChangeSet($em->getClassMetadata('BackBuilder\ClassContent\Revision'), $revision);
        } elseif ($uow->isScheduledForDelete($content)) {
            $revisions = $em->getRepository('BackBuilder\ClassContent\Revision')->getRevisions($content);
            foreach ($revisions as $revision) {
                $revision->setContent(NULL);
                $revision->setState(Revision::STATE_DELETED);
                $uow->computeChangeSet($em->getClassMetadata('BackBuilder\ClassContent\Revision'), $revision);
            }
        }
    }

    public static function onPostLoad(Event $event)
    {
        $revision = $event->getTarget();
        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();

        $classname = $revision->getClassname();
        if (NULL !== $application) {
        $em = $application->getEntityManager();
            $revision->setEntityManager($em)
                    ->setToken($application->getBBUserToken());
            
            if (NULL == $revision->getContent()) {
                $db = $em->getConnection();
                $stmt = $db->executeQuery("SELECT `content_uid`, `classname` FROM `revision` WHERE `uid` = ?", array($revision->getUid()), array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, 1, 1));

                $items = $stmt->fetchAll();
                if ($items) {
                    foreach ($items as $item) {
                        $content = $em->find($item["classname"], $item["content_uid"]); //@fixme ->use ResultSetMapping
                        if ($content)
                            $revision->setContent($content);
                    }
                }
            }
        }
        
        $revision->postLoad();
    }

    public static function onPrerenderContent(Event $event)
    {
        $dispatcher = $event->getDispatcher();
        if (NULL === $application = $dispatcher->getApplication())
            return;
        if (NULL === $token = $application->getBBUserToken())
            return;

        $renderer = $event->getEventArgs();
        if (!is_a($renderer, 'BackBuilder\Renderer\ARenderer'))
            return;

        $content = $renderer->getObject();
        if (!is_a($content, 'BackBuilder\ClassContent\AClassContent'))
            return;

        $em = $application->getEntityManager();
        if (NULL !== $revision = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $token)) {
            $content->setDraft($revision);
            $application->debug(sprintf('Revision found for `%s` content and `%s` user', $content->getUid(), $token->getUsername()));
        }

        if (false === ($content instanceof ContentSet)) {
            foreach ($content->getData() as $key => $subcontent) {
                if (NULL === $subcontent) {
                    $contenttype = $content->getAcceptedType($key);
                    if (0 === strpos($contenttype, 'BackBuilder\ClassContent\\')) {
                        if (NULL === $content->getDraft()) {
                            $revision = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $token, true);
                            $content->setDraft($revision);
                        }
                        $content->$key = new $contenttype();
                    }
                }
            }
        }
    }

}