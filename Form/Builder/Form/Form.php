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

namespace BackBuilder\Form\Builder\Form;

use BackBuilder\Form\Builder\Form\Element\Element;
use BackBuilder\Renderer\Renderer;

/**
 * Validator
 *
 * @category    BackBuilder
 * @package     BackBuilder\Form\Builder\Form
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class Form 
{
    const FORM_PARAMETER = 'form';
    const METHOD_PARAMETER = 'method';
    const ACTION_PARAMETER = 'action';
    const ROUTENAME_PARAMETER = 'routename';
    const URL_PARAMETER = 'url';
    const SUBMITLABEL_PARAMETER = 'submit_label';
    
    protected $renderer;

    protected $method = 'POST';
    protected $action = null;
    protected $items = array();
    protected $available_method = array('POST', 'GET');
    protected $template;
    protected $submit_label = 'Submit';
    
    public function __construct(Renderer $renderer, array $config = array())
    {
        $this->renderer = $renderer;
        $this->buildConfig($config);
    }
    
    private function buildConfig(array $config = array())
    {
        if (true === isset($config[self::FORM_PARAMETER])) {
            $cConfig = $config[self::FORM_PARAMETER];
            if (true === isset($cConfig[self::METHOD_PARAMETER])) {
                $this->method = $cConfig[self::METHOD_PARAMETER];
            }
            if (true === isset($cConfig[self::ACTION_PARAMETER])) {
                if (true === isset($cConfig[self::ACTION_PARAMETER][self::ROUTENAME_PARAMETER])) {
                    $this->action = $this->renderer->generateUrlByRouteName($cConfig[self::ACTION_PARAMETER][self::ROUTENAME_PARAMETER]);
                } else if (true === isset($cConfig[self::ACTION_PARAMETER][self::URL_PARAMETER])) {
                    $this->action = $cConfig[self::ACTION_PARAMETER][self::URL_PARAMETER];
                }
            }
            if (true === isset($cConfig[self::SUBMITLABEL_PARAMETER])) {
                $this->submit_label = $cConfig[self::SUBMITLABEL_PARAMETER];
            }
        }
    }
    
    public function getMethod()
    {
        return $this->method;
    }
    
    public function getAction()
    {
        return $this->action;
    }
    
    public function getItem($id)
    {
        if (true === isset($this->items[$id])) {
            return $this->items[$id];
        }
        
        return null;
    }
    
    public function getItems()
    {
        return $this->items;
    }
    
    public function getTemplate()
    {
        return __DIR__ . '/../Templates/scripts/form/form.twig';
    }
    
    public function getSubmitLabel()
    {
        return $this->submit_label;
    }
    
    public function setMethod($method)
    {
        if (true === in_array($method, $this->available_method)) {
            $this->method = $method;
        }
        
        throw new \InvalidArgumentException(sprintf('Method %s not supported', $method));
    }
    
    public function setAction($action)
    {
        if (false === empty($action)) {
            $this->action = $action;
        }
    }
    
    public function setItem($id, Element $element)
    {
        $this->items[$id] = $element;
    }
    
    public function setTemplate($template)
    {
        $this->template = $template;
    }
    
    public function setSubmitLabel($submit_label)
    {
        $this->submit_label = $submit_label;
    }
}
