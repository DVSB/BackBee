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

use BackBuilder\Util\File;
use BackBuilder\Event\Event;
use BackBuilder\Exception\BBException;
use BackBuilder\ClassContent\ContentSet;
use BackBuilder\ClassContent\Revision;
use BackBuilder\ClassContent\AClassContent;
use BackBuilder\ClassContent\Element\file as elementFile;
use BackBuilder\ClassContent\Exception\ClassContentException;
use BackBuilder\Security\Exception\SecurityException;
use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Listener to ClassContent events :
 *    - classcontent.onflush: occurs when a classcontent entity is mentioned for current flush
 *    - classcontent.include: occurs when autoloader include a classcontent definition
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class ClassContentListener
{
    /**
     * Add discriminator values to class MetaData when a content class is loaded
     * Occur on classcontent.include events
     * @access public
     * @param Event $event
     */
    public static function onInclude(Event $event)
    {
        $dispatcher = $event->getDispatcher();
        if (null !== $dispatcher->getApplication()) {
            $em = $dispatcher->getApplication()->getEntityManager();
            $discriminatorValue = get_class($event->getTarget());
            foreach (class_parents($discriminatorValue) as $classname) {
                $em->getClassMetadata($classname)->addDiscriminatorMapClass($discriminatorValue, $discriminatorValue);

                if ('BackBuilder\ClassContent\AClassContent' === $classname) {
                    break;
                }
            }
        }
    }

    public static function onFlushContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AClassContent)) {
            return;
        }

        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();
        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();
        if ($uow->isScheduledForInsert($content) || $uow->isScheduledForUpdate($content)) {
            if (null !== $content->getProperty('labelized-by')) {
                $elements = explode('->', $content->getProperty('labelized-by'));
                $owner = null;
                $element = null;
                $value = $content;
                foreach ($elements as $element) {
                    $owner = $value;

                    if (null !== $value) {
                        $value = $value->getData($element);
                        if ($value instanceof AClassContent && false == $em->contains($value)) {
                            $value = $em->find(get_class($value), $value->getUid());
                        }
                    }
                }

                $content->setLabel($value);
            }

            if (null === $content->getLabel()) {
                $content->setLabel($content->getProperty('name'));
            }

            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($content)), $content);
//
//            if (null !== $page = $content->getMainNode()) {
//                if (AClassContent::STATE_NORMAL === $content->getState()) {
//                    $page->setModified(new \DateTime());
//                    $method = 'computeChangeSet';
//                    if (true === $uow->isEntityScheduled($page)) {
//                        $method = 'recomputeSingleEntityChangeSet';
//                    }
//
//                    $uow->$method($em->getClassMetadata('BackBuilder\NestedNode\Page'), $page);
//                }
//            }
            //self::HandleContentMainnode($content,$application);
        }
    }

    public static function handleContentMainnode(AClassContent $content, $application)
    {
        if (!isset($content) && $content->isElementContent()) {
            return;
        }
    }

    /**
     * Occur on clascontent.preremove event
     * @param  \BackBuilder\Event\Event $event
     * @return type
     */
    public static function onPreRemove(Event $event)
    {
        return;
    }

    /**
     * Occurs on classcontent.update event
     * @param  Event       $event
     * @throws BBException Occurs on illegal targeted object or missing BackBuilder Application
     */
    public static function onUpdate(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AClassContent)) {
            throw new BBException('Enable to update object', BBException::INVALID_ARGUMENT, new \InvalidArgumentException(sprintf('Only BackBuilder\ClassContent\AClassContent can be commit, `%s` received', get_class($content))));
        }

        $dispatcher = $event->getDispatcher();
        if (null === $application = $dispatcher->getApplication()) {
            throw new BBException('Enable to update object', BBException::MISSING_APPLICATION, new \RuntimeException('BackBuilder application has to be initialized'));
        }

        if (null === $token = $application->getBBUserToken()) {
            throw new SecurityException('Enable to update : unauthorized user', SecurityException::UNAUTHORIZED_USER);
        }

        $em = $dispatcher->getApplication()->getEntityManager();
        if (null === $revision = $content->getDraft()) {
            if (null === $revision = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $token)) {
                throw new ClassContentException('Enable to get draft', ClassContentException::REVISION_MISSING);
            }
            $content->setDraft($revision);
        }

        $content->releaseDraft();
        if (0 == $revision->getRevision() || $revision->getRevision() == $content->getRevision) {
            throw new ClassContentException('Content is up to date', ClassContentException::REVISION_UPTODATE);
        }

        $lastCommitted = $em->getRepository('BackBuilder\ClassContent\Revision')->findBy(array('_content' => $content, '_revision' => $content->getRevision(), '_state' => Revision::STATE_COMMITTED));
        if (null === $lastCommitted) {
            throw new ClassContentException('Enable to get last committed revision', ClassContentException::REVISION_MISSING);
        }

        $content->updateDraft($lastCommitted);
    }

    /**
     * Occurs on classcontent.commit event
     * @param  Event                 $event
     * @throws BBException           Occurs on illegal targeted object or missing BackBuilder Application
     * @throws SecurityException     Occurs on missing valid BBUserToken
     * @throws ClassContentException Occurs on missing revision
     */
    public static function onCommit(Event $event)
    {
        $content = $event->getTarget();
        if (false === ($content instanceof AClassContent)) {
            throw new BBException('Enable to commit object', BBException::INVALID_ARGUMENT, new \InvalidArgumentException(sprintf('Only BackBuilder\ClassContent\AClassContent can be commit, `%s` received', get_class($content))));
        }

        $dispatcher = $event->getDispatcher();
        if (null === $application = $dispatcher->getApplication()) {
            throw new BBException('Enable to commit object', BBException::MISSING_APPLICATION, new \RuntimeException('BackBuilder application has to be initialized'));
        }

        if (null === $token = $application->getBBUserToken()) {
            throw new SecurityException('Enable to commit : unauthorized user', SecurityException::UNAUTHORIZED_USER);
        }

        $em = $dispatcher->getApplication()->getEntityManager();
        $content = $em->getRepository(ClassUtils::getRealClass($content))->load($content, $token);
        if (null === $revision = $content->getDraft()) {
            throw new ClassContentException('Enable to get draft', ClassContentException::REVISION_MISSING);
        }

        $content->prepareCommitDraft();

        if ($content instanceof ContentSet) {
            $content->clear();
            while ($subcontent = $revision->next()) {
                if ($subcontent instanceof AClassContent) {
                    $subcontent = $em->getRepository(ClassUtils::getRealClass($subcontent))->load($subcontent);
                    if (null !== $subcontent) {
                        $content->push($subcontent);
                    }
                }
            }
        } else {
            foreach ($revision->getData() as $key => $values) {
                $values = is_array($values) ? $values : array($values);
                foreach ($values as &$subcontent) {
                    if ($subcontent instanceof AClassContent) {
                        $subcontent = $em->getRepository(ClassUtils::getRealClass($subcontent))->load($subcontent);
                    }
                }
                unset($subcontent);

                $content->$key = $values;
            }

            if ($content instanceof elementFile) {
                $em->getRepository('BackBuilder\ClassContent\Element\file')
                        ->setDirectories($dispatcher->getApplication())
                        ->commitFile($content);
            }
        }

        $application->info(sprintf('`%s(%s)` rev.%d commited by user `%s`.', get_class($content), $content->getUid(), $content->getRevision(), $application->getBBUserToken()->getUsername()));
    }

    /**
     * Occurs on classcontent.revert event
     * @param  Event       $event
     * @throws BBException Occurs on illegal targeted object or missing BackBuilder Application
     */
    public static function onRevert(Event $event)
    {
        return;
    }

    public static function onRemoveElementFile(Event $event)
    {
        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();

        try {
            $content = $event->getEventArgs()->getEntity();
            if (!($content instanceof \BackBuilder\ClassContent\Element\file)) {
                return;
            }

            $includePath = array($application->getStorageDir(), $application->getMediaDir());
            if (null !== $application->getBBUserToken()) {
                $includePath[] = $application->getTemporaryDir();
            }

            $filename = $content->path;
            File::resolveFilepath($filename, null, array('include_path' => $includePath));

            @unlink($filename);
        } catch (\Exception $e) {
            $application->warning('Unable to delete file: '.$e->getMessage());
        }
    }

    /**
     * Occure on services.local.classcontent.postcall
     * @param \BackBuilder\Event\Event $event
     */
    public static function onServicePostCall(Event $event)
    {
        $service = $event->getTarget();
        if (false === is_a($service, 'BackBuilder\Services\Local\ClassContent')) {
            return;
        }

        self::_setRendermodeParameter($event);
    }

    /**
     * Dynamically add render modes options to the class
     * @param \BackBuilder\Event\Event $event
     */
    private static function _setRendermodeParameter(Event $event)
    {
        $application = $event->getDispatcher()->getApplication();
        if (null === $application) {
            return;
        }

        $method = $event->getArgument('method');
        if ('getContentParameters' !== $method) {
            return;
        }

        $result = $event->getArgument('result', array());
        if (false === array_key_exists('rendermode', $result)) {
            return;
        }
        if (false === array_key_exists('array', $result['rendermode'])) {
            return;
        }
        if (false === array_key_exists('options', $result['rendermode']['array'])) {
            return;
        }

        $params = $event->getArgument('params', array());
        if (false === array_key_exists('nodeInfos', $params)) {
            return;
        }
        if (false === array_key_exists('type', $params['nodeInfos'])) {
            return;
        }

        $classname = '\BackBuilder\ClassContent\\'.$params['nodeInfos']['type'];
        if (false === class_exists($classname)) {
            return;
        }

        $renderer = $application->getRenderer();
        $modes = array('default' => 'default');
        foreach ($renderer->getAvailableRenderMode(new $classname()) as $mode) {
            $modes[$mode] = $mode;
        }

        $result['rendermode']['array']['options'] = $modes;

        $event->setArgument('result', $result);
    }
}
