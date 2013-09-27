<?php

namespace BackBuilder\Renderer;

/**
 * Interface for the object that can be rendered
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
interface IRenderable
{

    /**
     * Returns data associated to $var for rendering assignation, all data if NULL provided
     * @param string $var
     * @return string|array|null
     */
    public function getData($var = null);

    /**
     * Returns parameters associated to $var for rendering assignation, all data if NULL provided
     * @param string $var
     * @return string|array|null
     */
    public function getParam($var = null);

    /**
     * Returns TRUE if the object can be rendered.
     * @return Boolean
     */
    public function isRenderable();
}