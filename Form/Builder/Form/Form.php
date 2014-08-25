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
 * Form
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
    
    /**
     * Form's constructor
     * 
     * @param \BackBuilder\Renderer\Renderer $renderer
     * @param array $config
     */
    public function __construct(Renderer $renderer, array $config = array())
    {
        $this->renderer = $renderer;
        $this->buildConfig($config);
    }
    
    /**
     * Build config with different parameters (Method, action, etc)
     * 
     * @param array $config
     */
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
    
    /**
     * Get the method of request
     * 
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
    
    /**
     * Get the action of form (url)
     * 
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Find element of form with id
     * 
     * @param string $id
     * @return null|Element
     */
    public function getItem($id)
    {
        if (true === isset($this->items[$id])) {
            return $this->items[$id];
        }
        
        return null;
    }
    
    /**
     * Get all element of form
     * 
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }
    
    /**
     * Get template file
     * 
     * @return string
     */
    public function getTemplate()
    {
        return __DIR__ . '/../Templates/scripts/form/form.twig';
    }
    
    /**
     * Get submit label
     * 
     * @return string
     */
    public function getSubmitLabel()
    {
        return $this->submit_label;
    }
    
    /**
     * Set method of form
     * 
     * @param string $method
     * @throws \InvalidArgumentException
     */
    public function setMethod($method)
    {
        if (true === in_array($method, $this->available_method)) {
            $this->method = $method;
        }
        
        throw new \InvalidArgumentException(sprintf('Method %s not supported', $method));
    }
    
    /**
     * Set action of form
     * 
     * @param string $action
     */
    public function setAction($action)
    {
        if (false === empty($action)) {
            $this->action = $action;
        }
    }
    
    /**
     * Put the element into items of form
     * 
     * @param string $id
     * @param \BackBuilder\Form\Builder\Form\Element\Element $element
     */
    public function setItem($id, Element $element)
    {
        $this->items[$id] = $element;
    }
    
    /**
     * Set the template
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }
    
    /**
     * Set the submit label
     * 
     * @param string $submit_label
     */
    public function setSubmitLabel($submit_label)
    {
        $this->submit_label = $submit_label;
    }
}
