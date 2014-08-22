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
 * Validator
 *
 * @category    BackBuilder
 * @package     BackBuilder\Form\Builder\Form\Element
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class Checkbox extends Element
{
    const OPTION_PARAMETER = 'options';
    const CHECKED_PARAMETER = 'checked';
    const INLINE_PARAMETER = 'inline';
    
    protected $options = array();
    protected $inline = false;
    
    public function __construct($key, array $config = array())
    {
        parent::__construct($key, $config);
        $this->buildCustomConfig($config);
        $this->type = 'checkbox';
        $this->template =  'form/checkbox';
    }
    
    public function buildCustomConfig(array $config = array())
    {
        if (true === isset($config[self::OPTION_PARAMETER])) {
            $this->options = $config[self::OPTION_PARAMETER];
        }
        if (true === isset($config[self::CHECKED_PARAMETER])) {
            $this->setValue($config[self::CHECKED_PARAMETER]);
        }
        
        if (true === isset($config[self::INLINE_PARAMETER]) && true === $config[self::INLINE_PARAMETER]) {
            $this->inline = true;
        }
        
    }
    
    public function getOptions()
    {
        return $this->options;
    }
    
    public function isInline()
    {
        return $this->inline;
    }
    
    public function setOptions(array $options = array())
    {
        $this->options = $options;
    }
    
    public function setInline($inline)
    {
        $this->inline = (bool) $inline;
    }
    
    public function setValue($value = array())
    {
        if (false === is_array($value)) {
            $value = (array) $value;
        }
        
        $this->value = $value;
    }
}
