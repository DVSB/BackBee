<?php

namespace BackBuilder\Renderer;

use BackBuilder\Renderer\Helper\HelperManager;
use BackBuilder\Site\Layout;

/**
 * abstract class for renderer adapter
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
abstract class ARendererAdapter implements IRendererAdapter
{
    /**
     * @var BackBuilder\Renderer\ARenderer
     */
    protected $renderer;

    /**
     * Constructor
     *
     * @param ARenderer $renderer
     */
    public function __construct(ARenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Magic call method; allow current object to forward unknow method to
     * its associated renderer
     *
     * @param  string $method
     * @param  array  $argv
     * @return mixed
     */
    public function __call($method, $argv)
    {
        return call_user_func_array(array($this->renderer, $method), $argv);
    }

    /**
     * @param HelperManager $helperManager [description]
     */
    public function setRenderer(ARenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @see BackBuilder\Renderer\IRendererAdapter::getManagedFileExtensions()
     */
    public function getManagedFileExtensions()
    {
        return array();
    }

    /**
     * @see BackBuilder\Renderer\IRendererAdapter::isValidTemplateFile()
     */
    public function isValidTemplateFile($filename, array $templateDir)
    {
        return false;
    }

    /**
     * @see BackBuilder\Renderer\IRendererAdapter::renderTemplate()
     */
    public function renderTemplate($filename, array $templateDir, array $params = array(), array $vars = array())
    {
        return '';
    }

    /**
     * @see BackBuilder\Renderer\IRendererAdapter::updateLayout()
     */
    public function updateLayout(Layout $layout, $layoutFile)
    {
    }

    /**
     * @see BackBuilder\Renderer\IRendererAdapter::onNewRenderer()
     */
    public function onNewRenderer(ARenderer $renderer)
    {
        $this->setRenderer($renderer);
    }

    /**
     * @see BackBuilder\Renderer\IRendererAdapter::onRestorePreviousRenderer()
     */
    public function onRestorePreviousRenderer(ARenderer $renderer)
    {
        $this->setRenderer($renderer);
    }
}
