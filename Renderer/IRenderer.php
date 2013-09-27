<?php
namespace BackBuilder\Renderer;

use BackBuilder\Renderer\IRenderable,
    BackBuilder\Site\Layout;

/**
 * Interface for the templates renderers
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @copyright   Lp system
 * @author      c.rouillon
 */
interface IRenderer {
    public function assign($var, $value = NULL);
    public function getAssignedVars();
    public function render(IRenderable $content = NULL, $mode = NULL, $params = NULL, $template = NULL);
    public function partial($template = NULL, $params = NULL);
    public function error($error_code, $title = NULL, $message = NULL, $trace = NULL);
    public function updateLayout(Layout $layout);
    public function removeLayout(Layout $layout);
}