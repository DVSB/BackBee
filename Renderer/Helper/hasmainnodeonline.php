<?php
namespace BackBuilder\Renderer\Helper;

use BackBuilder\NestedNode\Page;

use BackBuilder\ClassContent\AClassContent;

class hasmainnodeonline extends AHelper {
    public function __invoke(AClassContent $content = NULL) {
        if (NULL === $content)
            $content = $this->_renderer->getObject();
        
        return !(NULL === $this->_renderer->getApplication()->getBBUserToken() && is_object($content->getMainNode()) && !($content->getMainNode()->getState() & Page::STATE_ONLINE));
    }
}