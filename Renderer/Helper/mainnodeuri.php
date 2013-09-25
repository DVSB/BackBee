<?php
namespace BackBuilder\Renderer\Helper;

use BackBuilder\ClassContent\AClassContent;

class mainnodeuri extends AHelper {
    public function __invoke(AClassContent $content = NULL) {
        if (NULL === $content)
            $content = $this->_renderer->getObject();
        if(is_a($content, 'BackBuilder\ClassContent\AClassContent')) {
            $page = $content->getMainNode();
            if (NULL !== $page) {
                return $this->_renderer->getUri($page->getUrl());
            }
        }
        
        return '#';
    }
}