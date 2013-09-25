<?php
namespace BackBuilder\Renderer\Helper;

use BackBuilder\Renderer\ARenderer;
use Doctrine\Common\Collections\ArrayCollection;

class container extends AHelper {
    protected $_container;
    
    public function __construct(ARenderer $renderer) {
        parent::__construct($renderer);
        
        $this->_container = new ArrayCollection();
    }
    
    public function __invoke() {
        return $this->_container;
    }
}