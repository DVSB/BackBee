<?php
namespace BackBuilder\Renderer\Helper;

use BackBuilder\Renderer\ARenderer;

abstract class AHelper {
    /**
     * @var \BackBuilder\Renderer\ARenderer
     */
    protected $_renderer;

    /**
     * Class constructor
     * @param \BackBuilder\Renderer\ARenderer $renderer
     */
    public function __construct(ARenderer $renderer) {
        $this->setRenderer($renderer);
    }

    /**
     * Set the renderer
     * @param \BackBuilder\Renderer\ARenderer $renderer
     * @return \BackBuilder\Renderer\Helper\AHelper
     */
    public function setRenderer(ARenderer $renderer) {
        $this->_renderer = $renderer;
        return $this;
    }
}