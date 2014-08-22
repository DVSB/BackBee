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
class Number extends Text
{
    const MIN_PARAMETER = 'min';
    const MAX_PARAMETER = 'max';
    const STEP_PARAMETER = 'step';
    
    protected $min = null;
    protected $max = null;
    protected $step = null;
    
    public function __construct($key, array $config = array())
    {
        parent::__construct($key, $config);
        $this->buildCustomConfig($config);
        $this->type = 'number';
        $this->template =  'form/number';
    }
    
    private function buildCustomConfig($config)
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
    
    public function getMin()
    {
        return $this->min;
    }
    
    public function getMax()
    {
        return $this->max;
    }
    
    public function getStep()
    {
        return $this->step;
    }
    
    public function setMin($min)
    {
        $this->min = (int) $min;
    }
    
    public function setMax($max)
    {
        $this->max = (int) $max;
    }
    
    public function setStep($step)
    {
        $this->step = $step;
    }
}
