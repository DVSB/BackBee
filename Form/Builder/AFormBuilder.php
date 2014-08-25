<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 * 
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\Form\Builder;

use BackBuilder\Renderer\Renderer;

/**
 * AFormBuilder
 *
 * @category    BackBuilder
 * @package     BackBuilder\Form\Builder
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
abstract class AFormBuilder 
{
    const PREFIX_ELEMENT_CLASSNAME = 'BackBuilder\\Form\\Builder\\Form\\Element\\';
    
    protected $config;
    protected $renderer;
    
    /**
     * AFormBuilder's contructor
     * 
     * @param \BackBuilder\Renderer\Renderer $renderer
     */
    public function __construct(Renderer $renderer)
    {
        $this->renderer = $renderer;
        $renderer->addScriptDir(__DIR__ . '/Templates/scripts');
    }
    
    /**
     * Create form with config and return html
     */
    public abstract function createForm();

    /**
     * Set the config to form builder
     * 
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function build(array $config = array())
    {
        if (true === empty($config)) {
            throw new \InvalidArgumentException(sprintf('config must be set'));
        }
        $this->config = $config;
    }
    
    /**
     * Return the classname of element, null otherwise
     * 
     * @param string $element
     * @return string|null
     */
    public function getElementClassname($element)
    {
        $classname = self::PREFIX_ELEMENT_CLASSNAME . ucfirst($element);
        if (true === class_exists($classname)) {
            return $classname;
        }
        
        return null;
    }
}
