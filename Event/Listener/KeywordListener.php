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

use BackBuilder\Event\Event;

/**
 * Listener to keyword events
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class KeywordListener
{

    private static function isAcceptedKeywordValue($content = array())
    {
        $founded = false;
        foreach ($content as $value) {
            if (is_array($value) && $founded != true) {
                $founded = self::isAcceptedKeywordValue($value);
            } else {
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
            if (null === $application)
                return;

            $em = $application->getEntityManager();

            $renderer = $event->getEventArgs();
            if (!is_a($renderer, 'BackBuilder\Renderer\ARenderer'))
                return;

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
                                if ($item !== $content)
                                    $similarContent[$item->getUid()] = $item;
                        }
                    }
                } else if (NULL !== $content->keywords) {
                    if (NULL !== $application->getBBUserToken() && NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content->keywords, $application->getBBUserToken()))
                        $content->keywords->setDraft($draft);
                    $realKeyword = $em->find('BackBuilder\NestedNode\KeyWord', $content->keywords->value);
                    if (NULL !== $realKeyword)
                        foreach ($realKeyword->getContent()->toArray() as $item)
                            if ($item !== $content)
                                $similarContent[$item->getUid()] = $item;
                }

                //var_dump(array_keys(($similarContent)));
                //die();
                //var_dump(array_unique($similarContent));
                $renderer->assign('similarcontent', array_splice($similarContent, 0, 10));
            }
        }
    }

}
