<?php
namespace BackBuilder\Renderer\Helper;

class translate extends AHelper {
    public function __invoke($string) {
        $translator = $this->_renderer->getApplication()->getContainer()->get('translator');
        
        return $translator->trans($string);
    }
}