<?php

namespace BackBuilder\Event\Listener;

use BackBuilder\Event\Event;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of KeywordListener
 *
 * @author nicolas
 */
class KeywordListener {
    
    private static function isAcceptedKeywordValue($content = array())
    {
        $founded = false;
        foreach ($content as $value)
        {
            if (is_array($value) && $founded != true) {
                $founded = self::isAcceptedKeywordValue($value);
            }
            else {
                if ('BackBuilder\ClassContent\Element\keyword' == $value) {
                    $founded = true;
                    return $founded;
                }
            }
        }
        return $founded;
    }
    
    public static function onRender(Event $event)
    {
        $dispatcher = $event->getDispatcher();
        
        if (null !== $dispatcher) {
            
            $application = $dispatcher->getApplication();
            if (null === $application) return;
            
            $em = $application->getEntityManager();
            
            $renderer = $event->getEventArgs();
            if (!is_a($renderer, 'BackBuilder\Renderer\ARenderer')) return;
            
            $content = $renderer->getObject();
            $founded = self::isAcceptedKeywordValue($content->getAccept());
            if (TRUE === $founded) {
                $similarContent = array();
                if (is_array($content->keywords)) {
                    foreach ($content->keywords as $key) {
                        //var_dump($key->getData());
                        if (NULL !== $application->getBBUserToken() && NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($key, $application->getBBUserToken()))
                            $key->setDraft($draft);
                        $realKeyword = $em->find('BackBuilder\NestedNode\KeyWord', $key->value);
                        if (NULL !== $realKeyword) {
                            foreach ($realKeyword->getContent()->toArray() as $item)
                                if ($item !== $content) $similarContent[$item->getUid()] = $item;
                            
                        }
                    }
                } else if (NULL !== $content->keywords) {
                    if (NULL !== $application->getBBUserToken() && NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content->keywords, $application->getBBUserToken()))
                        $content->keywords->setDraft($draft);
                    $realKeyword = $em->find('BackBuilder\NestedNode\KeyWord', $content->keywords->value);
                    if (NULL !== $realKeyword) 
                    foreach ($realKeyword->getContent()->toArray() as $item)
                        if ($item !== $content) $similarContent[$item->getUid()] = $item;
                }
                
                //var_dump(array_keys(($similarContent)));
                //die();
                //var_dump(array_unique($similarContent));
                $renderer->assign('similarcontent', array_splice($similarContent, 0, 10));
            }
                
        }
    }
}
