<?php

namespace BackBuilder\Renderer\Helper;

class breadcrumb extends AHelper {

    public function __invoke() {
        $application = $this->_renderer->getApplication();
        $ancestors = array();
        if (NULL !== $application) {
            $em = $application->getEntityManager();
            if (NULL !== $current = $this->_renderer->getCurrentPage()) {
                $ancestors = $em->getRepository('BackBuilder\NestedNode\Page')->getAncestors($current);
            } else {
                $ancestors = array($this->_renderer->getCurrentRoot());
            }
        }
       return $ancestors;
    }

}