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

namespace BackBuilder\Form\Builder\Form\Element;

use BackBuilder\Renderer\IRenderable;

/**
 * Validator
 *
 * @category    BackBuilder
 * @package     BackBuilder\Form\Builder\Form\Element
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class Element implements IRenderable 
{

    const PLACEHOLDER = 'placeholder';
    const VALUE = 'value';
    const LABEL = 'label';

    protected $uid;
    protected $type = null;
    protected $label = null;
    protected $placeholder = null;
    protected $value = null;
    protected $template;

    public function __construct($key, array $config = array()) 
    {
        $this->uid = $key;
        $this->label = ucfirst($key);
        $this->buildConfig($config);
    }

    private function buildConfig(array $config = array()) 
    {
        if (true === isset($config[self::PLACEHOLDER])) {
            $this->placeholder = $config[self::PLACEHOLDER];
        }
        if (true === isset($config[self::VALUE])) {
            $this->value = $config[self::VALUE];
        }
        if (true === isset($config[self::LABEL])) {
            $this->label = $config[self::LABEL];
        }
    }

    public function getUid() 
    {
        $this->uid;
    }

    public function getType() 
    {
        return $this->type;
    }

    public function getLabel()
    {
        return $this->label;
    }
    
    public function getPlaceholder() 
    {
        return $this->placeholder;
    }

    public function getValue() 
    {
        return $this->value;
    }

    public function setType($type) 
    {
        if (true === class_exists("BackBuilder\\Form\\Builder\\Form\\Element\\" . $type)) {
            $this->type = $type;
        } else {
            throw new \InvalidArgumentException(sprintf('type %s not found', $type));
        }
    }
    
    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function setPlaceholder($placeholder) 
    {
        $this->placeholder = $placeholder;
    }
    
    public function setValue($value) 
    {
        $this->value = $value;
    }

    public function setTemplate($template) 
    {
        $this->template = $template;
    }
    
    /*     * ******************************************* */
    /*     * ********** IMPLEMENTS INTERFACE *********** */
    /*     * ******************************************* */

    /**
     * Returns data associated to $var for rendering assignation, all data if NULL provided
     * @param string $var
     * @return string|array|null
     */
    public function getData($var = null) 
    {
        return null;
    }

    /**
     * Returns parameters associated to $var for rendering assignation, all data if NULL provided
     * @param string $var
     * @return string|array|null
     */
    public function getParam($var = null) 
    {
        return null;
    }

    /**
     * Returns TRUE if the object can be rendered.
     * @return Boolean
     */
    public function isRenderable() 
    {
        return true;
    }

    /**
     * Returns return the entity template name
     * @return string
     */
    public function getTemplateName() 
    {
        return $this->template;
    }

    public function getMode() 
    {
        return null;
    }
    
    public function getDraft()
    {
        return null;
    }
}
