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
 * Number
 *
 * @category    BackBuilder
 * @package     BackBuilder\Form\Builder\Form\Element
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class Number extends Text
{
    const MIN_PARAMETER = 'min';
    const MAX_PARAMETER = 'max';
    const STEP_PARAMETER = 'step';
    
    protected $min = null;
    protected $max = null;
    protected $step = null;
    
    /**
     * Number's constructor
     * 
     * @param string $key
     * @param array $config
     * @param string $value
     */
    public function __construct($key, array $config = array(), $value = null)
    {
        parent::__construct($key, $config, $value);
        $this->buildCustomConfig($config);
        $this->type = 'number';
        $this->template =  'form/number';
    }
    
    /**
     * Build config with different parameters
     * 
     * @param array $config
     */
    private function buildCustomConfig(array $config = array())
    {
        if (true === isset($config[self::MIN_PARAMETER])) {
            $this->min = $config[self::MIN_PARAMETER];
        }
        if (true === isset($config[self::MAX_PARAMETER])) {
            $this->max = $config[self::MAX_PARAMETER];
        }
        if (true === isset($config[self::STEP_PARAMETER])) {
            $this->step = $config[self::STEP_PARAMETER];
        }
    }
    
    /**
     * Get min
     * 
     * @return integer
     */
    public function getMin()
    {
        return $this->min;
    }
    
    /**
     * Get max
     * 
     * @return integer
     */
    public function getMax()
    {
        return $this->max;
    }
    
    /**
     * Get step
     * 
     * @return integer
     */
    public function getStep()
    {
        return $this->step;
    }
    
    /**
     * Set min
     * 
     * @param integer $min
     * @return \BackBuilder\Form\Builder\Form\Element\Number
     */
    public function setMin($min)
    {
        $this->min = (int) $min;
        return $this;
    }
    
    /**
     * Set max
     * 
     * @param integer $max
     * @return \BackBuilder\Form\Builder\Form\Element\Number
     */
    public function setMax($max)
    {
        $this->max = (int) $max;
        return $this;
    }
    
    /**
     * Set step
     * 
     * @param integer $step
     * @return \BackBuilder\Form\Builder\Form\Element\Number
     */
    public function setStep($step)
    {
        $this->step = $step;
        return $this;
    }
}
