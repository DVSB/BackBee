<?php

namespace BackBuilder\Renderer\Helper;

class currentpage extends AHelper {

    protected $_currentPage;

    public function getCurrentPage() {
        if (NULL === $this->_currentPage) {
            $currentpage = $this->_renderer->getCurrentPage();
            if (is_object($currentpage) && (is_a($currentpage, '\BackBuilder\NestedNode\Page'))) {
                $this->_currentPage = $currentpage;
            }

            if (NULL === $this->_currentPage)
                $this->_currentPage = new \BackBuilder\NestedNode\Page();
        }

        return $this->_currentPage;
    }

    public function __invoke() {
        return $this->getCurrentPage();
    }

}