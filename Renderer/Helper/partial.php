<?php
namespace BackBuilder\Renderer\Helper;

class partial extends AHelper {
    public function __invoke($template, $params = NULL) {
        $renderer = $this->_renderer;
        
        if (NULL !== $renderer) {
            return $renderer->partial($template, $params);
        }
        
        return;
    }
}