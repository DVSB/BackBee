<?php

namespace BackBuilder\Renderer\Helper;

class page extends AHelper {

    protected $_page;

    public function getTarget() {
        
        if (NULL === $this->_renderer->getApplication()->getBBUserToken()) {
            return $this->_page->getTarget();
        } else {
            return '_self';
        }
    }

    public function __invoke(\BackBuilder\NestedNode\Page $page) {
        $this->_page = $page;
        
        return $this;
    }

}