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

/**
 * Select
 *
 * @category    BackBuilder
 * @package     BackBuilder\Form\Builder\Form\Element
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class Select extends Element
{
    const OPTION_PARAMETER = 'options';
    const SELECTED_PARAMETER = 'selected';
    const MULTIPLE_PARAMETER = 'multiple';
    
    protected $options = array();
    protected $multiple = false;
    
    /**
     * Select's constructor
     * 
     * @param string $key
     * @param array $config
     * @param string $value
     */
    public function __construct($key, array $config = array(), $value = null, $error = null)
    {
        parent::__construct($key, $config, $value, $error);
        $this->buildCustomConfig($config);
        $this->type = 'select';
        $this->template =  'form/select';
    }
    
    /**
     * Build config with different parameters
     * 
     * @param array $config
     */
    public function buildCustomConfig(array $config = array())
    {
        if (true === isset($config[self::OPTION_PARAMETER])) {
            $this->options = $config[self::OPTION_PARAMETER];
        }
        if (true === isset($config[self::SELECTED_PARAMETER])) {
            $this->setValue($config[self::SELECTED_PARAMETER]);
        }
        if (true === isset($config[self::MULTIPLE_PARAMETER]) && true === $config[self::MULTIPLE_PARAMETER]) {
            $this->multiple = true;
        }
    }
    
    /**
     * Get options
     * 
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
    
    /**
     * Get multiple
     * 
     * @return boolean
     */
    public function isMultiple()
    {
        return $this->multiple;
    }
    
    /**
     * Set options
     * 
     * @param array $options
     * @return \BackBuilder\Form\Builder\Form\Element\Select
     */
    public function setOptions(array $options = array())
    {
        $this->options = $options;
        return $this;
    }
    
    /**
     * Set multiple
     * 
     * @param boolean $multiple
     * @return \BackBuilder\Form\Builder\Form\Element\Select
     */
    public function setMultiple($multiple)
    {
        $this->multiple = (bool) $multiple;
        return $this;
    }
    
    /**
     * Set value
     * 
     * @param mixed $value
     * @return \BackBuilder\Form\Builder\Form\Element\Select
     */
    public function setValue($value = array())
    {
        if (false === is_array($value)) {
            $value = (array) $value;
        }
        
        $this->value = $value;
        return $this;
    }
}
