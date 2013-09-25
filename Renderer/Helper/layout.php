<?php
namespace BackBuilder\Renderer\Helper;

use BackBuilder\Site\Layout as EmptyLayout;

class layout extends AHelper {
    protected $_layout;
    
    public function getLayout() {
        if (NULL === $this->_layout) {
            $object = $this->_renderer->getObject();
            if (is_object($object) && method_exists($object, 'getLayout')) {
                $layout = $object->getLayout();
                if (is_a($layout, '\BackBuilder\Site\Layout'))
                    $this->_layout = $layout;
            }
            
            if (NULL === $this->_layout)
                $this->_layout = new EmptyLayout();
        }
        
        return $this->_layout;
    }
    
    public function __invoke() {
        return $this->getLayout();
    }
}