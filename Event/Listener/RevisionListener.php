<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Event\Listener;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\ContentSet;
use BackBee\ClassContent\Element\File as ElementFile;
use BackBee\ClassContent\Element\Image as ElementImage;
use BackBee\ClassContent\Revision;
use BackBee\Event\Event;
use BackBee\Util\Media;
use BackBee\Utils\File\File;

/**
 * Listener to ClassContent events :
 *    - classcontent.onflush: occurs when a classcontent entity is mentioned for current flush.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class RevisionListener
{
    public static function onRemoveContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AbstractClassContent)) {
            return;
        }

        $dispatcher = $event->getDispatcher();
        if (null === $dispatcher) {
            return;
        }

        $application = $dispatcher->getApplication();
        if (null === $application) {
            return;
        }

        $em = $application->getEntityManager();

        $revisions = $em->getRepository('BackBee\ClassContent\Revision')->getRevisions($content);
        foreach ($revisions as $revision) {
            $revision->setContent(null);
            $revision->setState(Revision::STATE_DELETED);
        }
    }

    public static function onFlushContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AbstractClassContent)) {
            return;
        }

        $dispatcher = $event->getDispatcher();
        if (null === $dispatcher) {
            return;
        }

        $application = $dispatcher->getApplication();
        if (null === $application) {
            return;
        }

        $token = $application->getSecurityContext()->getToken();
        if (null === $token) {
            return;
        }
        if ('BackBee\Security\Token\BBUserToken' != get_class($token)) {
            return;
        }

        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();

        if ($uow->isScheduledForInsert($content) && AbstractClassContent::STATE_NEW == $content->getState()) {
            $revision = $em->getRepository('BackBee\ClassContent\Revision')->checkout($content, $token);
            $em->persist($revision);
            $uow->computeChangeSet($em->getClassMetadata('BackBee\ClassContent\Revision'), $revision);
        } elseif ($uow->isScheduledForDelete($content)) {
            $revisions = $em->getRepository('BackBee\ClassContent\Revision')->getRevisions($content);
            foreach ($revisions as $revision) {
                $revision->setContent(null);
                $revision->setState(Revision::STATE_DELETED);
                $uow->computeChangeSet($em->getClassMetadata('BackBee\ClassContent\Revision'), $revision);
            }
        }
    }

    public static function onPreFlushElementFile(Event $event)
    {
        $revision = $event->getTarget();
        $content = $revision->getContent();
        
        if (!$content instanceof ElementFile) {
            return;
        }
        
        $application = $event->getApplication();
        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();
        
        if ($uow->isScheduledForDelete($content)) {
            return;
        }
        
        $fileRepository = $em->getRepository('BackBee\ClassContent\Element\File');
        $fileRepository->setDirectories($application);
        
        $fileRepository->commitFile($content);

        $moveFrom = $content->path;
        File::resolveFilepath($moveFrom, null, array('base_dir' => $application->getMediaDir()));

        $content->setParam('stat', json_encode(stat($moveFrom)));

        if ($content instanceof ElementImage) {

            list($width, $height) = getimagesize($moveFrom);

            $content->setParam('width', $width);
            $content->setParam('height', $height);
        }
    }

    public static function onPostLoad(Event $event)
    {
        $revision = $event->getTarget();
        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();

        if (null !== $application) {
            $em = $application->getEntityManager();
            $revision->setEntityManager($em)
                    ->setToken($application->getBBUserToken());

            if (null === $revision->getContent()) {
                $db = $em->getConnection();
                $stmt = $db->executeQuery("SELECT `content_uid`, `classname` FROM `revision` WHERE `uid` = ?", array($revision->getUid()));

                $items = $stmt->fetchAll();
                if ($items) {
                    foreach ($items as $item) {
                        $content = $em->find($item["classname"], $item["content_uid"]);
                        if ($content) {
                            $revision->setContent($content);
                        }
                    }
                }
            }
        }

        $revision->postLoad();
    }

    public static function onPrerenderContent(Event $event)
    {
        $dispatcher = $event->getDispatcher();
        if (null === $application = $dispatcher->getApplication()) {
            return;
        }
        if (null === $token = $application->getBBUserToken()) {
            return;
        }

        $renderer = $event->getEventArgs();
        if (!is_a($renderer, 'BackBee\Renderer\AbstractRenderer')) {
            return;
        }

        $content = $renderer->getObject();
        if (!is_a($content, 'BackBee\ClassContent\AbstractClassContent')) {
            return;
        }

        $em = $application->getEntityManager();
        if (null !== $revision = $em->getRepository('BackBee\ClassContent\Revision')->getDraft($content, $token)) {
            $content->setDraft($revision);
            $application->debug(sprintf('Revision found for `%s` content and `%s` user', $content->getUid(), $token->getUsername()));
        }

        if (false === ($content instanceof ContentSet)) {
            foreach ($content->getData() as $key => $subcontent) {
                if (null === $subcontent) {
                    $contenttype = $content->getAcceptedType($key);
                    if (0 === strpos($contenttype, 'BackBee\ClassContent\\')) {
                        if (null === $content->getDraft()) {
                            $revision = $em->getRepository('BackBee\ClassContent\Revision')->getDraft($content, $token, true);
                            $content->setDraft($revision);
                        }
                        $content->$key = new $contenttype();
                    }
                }
            }
        }
    }
}
