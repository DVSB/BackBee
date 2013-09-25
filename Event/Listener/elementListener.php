<?php

namespace BackBuilder\Event\Listener;

use BackBuilder\Event\Event;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of elementListener
 * @author nicolas
 */
class elementListener {

    public static function onRender(Event $event) {
        $dispatcher = $event->getDispatcher();
        $renderer = $event->getEventArgs();
        $renderer->assign('keyword', null);
        if (null !== $dispatcher) {
            $application = $dispatcher->getApplication();
            if (null === $application){return;}
            $keywordloaded = $event->getTarget();
            if (!is_a($renderer, 'BackBuilder\Renderer\ARenderer'))
                return;
            $keyWord = $application->getEntityManager()->find('BackBuilder\NestedNode\KeyWord', $keywordloaded->value);
            if (!is_null($keyWord)) {
                $renderer->assign('keyword', $keyWord);
            }
        }
    }

}
