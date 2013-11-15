<?php

namespace BackBuilder\Event\Listener;

use BackBuilder\Util\File;
use BackBuilder\Event\Event,
    BackBuilder\Exception\BBException,
    BackBuilder\ClassContent\ContentSet,
    BackBuilder\ClassContent\Revision,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\Element\file as elementFile,
    BackBuilder\ClassContent\Exception\ClassContentException,
    BackBuilder\Security\Exception\SecurityException;

/**
 * Listener to ClassContent events :
 *    - classcontent.onflush: occurs when a classcontent entity is mentioned for current flush
 *    - classcontent.include: occurs when autoloader include a classcontent definition
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event\Listener
 * @copyright   Lp system
 * @author      c.rouillon
 */
class ClassContentListener
{

    /**
     * Occur on classcontent.include events
     * @access public
     * @param Event $event
     */
    public static function onInclude(Event $event)
    {
        $dispatcher = $event->getDispatcher();

        if (NULL !== $dispatcher->getApplication()) {
            foreach (class_parents(get_class($event->getTarget())) as $classname) {
                $dispatcher->getApplication()->getEntityManager()
                        ->getClassMetadata($classname)
                        ->addDiscriminatorMapClass(get_class($event->getTarget()), get_class($event->getTarget()));
            }
        }
    }

    public static function onFlushContent(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AClassContent))
            return;

        $dispatcher = $event->getDispatcher();
        $application = $dispatcher->getApplication();
        $em = $application->getEntityManager();
        $uow = $em->getUnitOfWork();
        if ($uow->isScheduledForInsert($content) || $uow->isScheduledForUpdate($content)) {
            if (null !== $content->getProperty('labelized-by')) {
                $elements = explode('->', $content->getProperty('labelized-by'));
                $owner = NULL;
                $element = NULL;
                $value = $content;
                foreach ($elements as $element) {
                    $owner = $value;

                    if (NULL !== $value) {
                        $value = $value->getData($element);
                        if ($value instanceof AClassContent && false == $em->contains($value))
                            $value = $em->find(get_class($value), $value->getUid());
                    }
                }

                $content->setLabel($value);
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($content)), $content);
            }

            if (null === $content->getLabel()) {
                $content->setLabel($content->getProperty('name'));
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($content)), $content);
            }

            //self::HandleContentMainnode($content,$application);
        }
    }

    public static function handleContentMainnode(AClassContent $content, $application)
    {
        if (!isset($content) && $content->isElementContent())
            return;
    }

    /**
     * Occur on clascontent.preremove event
     * @param \BackBuilder\Event\Event $event
     * @return type
     */
    public static function onPreRemove(Event $event)
    {
        return;
    }

    /**
     * Occurs on classcontent.update event
     * @param Event $event
     * @throws BBException Occurs on illegal targeted object or missing BackBuilder Application
     */
    public static function onUpdate(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AClassContent))
            throw new BBException('Enable to update object', BBException::INVALID_ARGUMENT, new \InvalidArgumentException(sprintf('Only BackBuilder\ClassContent\AClassContent can be commit, `%s` received', get_class($content))));

        $dispatcher = $event->getDispatcher();
        if (NULL === $application = $dispatcher->getApplication())
            throw new BBException('Enable to update object', BBException::MISSING_APPLICATION, new \RuntimeException('BackBuilder application has to be initialized'));

        if (NULL === $token = $application->getBBUserToken())
            throw new SecurityException('Enable to update : unauthorized user', SecurityException::UNAUTHORIZED_USER);

        $em = $dispatcher->getApplication()->getEntityManager();
        if (NULL === $revision = $content->getDraft()) {
            if (NULL === $revision = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $token))
                throw new ClassContentException('Enable to get draft', ClassContentException::REVISION_MISSING);
            $content->setDraft($revision);
        }

        $content->releaseDraft();
        if (0 == $revision->getRevision() || $revision->getRevision() == $content->getRevision)
            throw new ClassContentException('Content is up to date', ClassContentException::REVISION_UPTODATE);

        $lastCommitted = $em->getRepository('BackBuilder\ClassContent\Revision')->findBy(array('_content' => $content, '_revision' => $content->getRevision(), '_state' => Revision::STATE_COMMITTED));
        if (NULL === $lastCommitted)
            throw new ClassContentException('Enable to get last committed revision', ClassContentException::REVISION_MISSING);

        $content->updateDraft($lastCommitted);
    }

    /**
     * Occurs on classcontent.commit event
     * @param Event $event
     * @throws BBException Occurs on illegal targeted object or missing BackBuilder Application
     * @throws SecurityException Occurs on missing valid BBUserToken
     * @throws ClassContentException Occurs on missing revision
     */
    public static function onCommit(Event $event)
    {
        $content = $event->getTarget();
        if (!($content instanceof AClassContent))
            throw new BBException('Enable to commit object', BBException::INVALID_ARGUMENT, new \InvalidArgumentException(sprintf('Only BackBuilder\ClassContent\AClassContent can be commit, `%s` received', get_class($content))));

        $dispatcher = $event->getDispatcher();
        if (NULL === $application = $dispatcher->getApplication())
            throw new BBException('Enable to commit object', BBException::MISSING_APPLICATION, new \RuntimeException('BackBuilder application has to be initialized'));

        if (NULL === $token = $application->getBBUserToken())
            throw new SecurityException('Enable to commit : unauthorized user', SecurityException::UNAUTHORIZED_USER);

        $em = $dispatcher->getApplication()->getEntityManager();
        if (NULL === $revision = $content->getDraft()) {
            if (NULL === $revision = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $token))
                throw new ClassContentException('Enable to get draft', ClassContentException::REVISION_MISSING);
            $content->setDraft($revision);
        }

        $content->prepareCommitDraft();

        if ($content instanceof ContentSet) {
            $content->clear();
            while ($subcontent = $revision->next()) {
                if ($subcontent instanceof AClassContent && !$em->contains($subcontent)) {
                    $subcontent = $em->find(get_class($subcontent), $subcontent->getUid());
                    if (NULL !== $subcontent)
                        $content->push($subcontent);
                }
            }
        } else {
            foreach ($revision->getData() as $key => $values) {
                $values = is_array($values) ? $values : array($values);
                foreach ($values as &$subcontent) {
                    if ($subcontent instanceof AClassContent) {
                        $subcontent = $em->find(get_class($subcontent), $subcontent->getUid());
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
     * @param Event $event
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
            if (!($content instanceof \BackBuilder\ClassContent\Element\file))
                return;


            $includePath = array($application->getStorageDir(), $application->getMediaDir());
            if (NULL !== $application->getBBUserToken())
                $includePath[] = $application->getTemporaryDir();

            $filename = $content->path;
            File::resolveFilepath($filename, NULL, array('include_path' => $includePath));

            @unlink($filename);
        } catch (\Exception $e) {
            $application->warning('Unable to delete file: ' . $e->getMessage());
        }
    }

    /**
     * Occure on services.local.classcontent.postcall
     * @param \BackBuilder\Event\Event $event
     */
    public static function onServicePostCall(Event $event)
    {
        $service = $event->getTarget();
        if (false === is_a($service, 'BackBuilder\Services\Local\ClassContent'))
            return;

        self::_setRendermodeParameter($event);
    }

    /**
     * Dynamically add render modes options to the class
     * @param \BackBuilder\Event\Event $event
     */
    private static function _setRendermodeParameter(Event $event)
    {
        $application = $event->getDispatcher()->getApplication();
        if (NULL === $application)
            return;

        $method = $event->getArgument('method');
        if ('getContentParameters' !== $method)
            return;

        $result = $event->getArgument('result', array());
        if (false === array_key_exists('rendermode', $result))
            return;
        if (false === array_key_exists('array', $result['rendermode']))
            return;
        if (false === array_key_exists('options', $result['rendermode']['array']))
            return;

        $params = $event->getArgument('params', array());
        if (false === array_key_exists('nodeInfos', $params))
            return;
        if (false === array_key_exists('type', $params['nodeInfos']))
            return;

        $classname = '\BackBuilder\ClassContent\\' . $params['nodeInfos']['type'];
        if (false === class_exists($classname))
            return;

        $renderer = $application->getRenderer();
        $modes = array('default' => 'default');
        foreach($renderer->getAvailableRenderMode(new $classname()) as $mode) {
            $modes[$mode] = $mode;
        }
        
        $result['rendermode']['array']['options'] = $modes;

        $event->setArgument('result', $result);
    }

}