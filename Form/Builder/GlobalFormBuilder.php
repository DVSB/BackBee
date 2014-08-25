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

use BackBuilder\Form\Builder\AFormBuilder;
use BackBuilder\Renderer\Renderer;
use BackBuilder\Form\Builder\Form\Form;

/**
 * GlobalFormBuilder
 *
 * @category    BackBuilder
 * @package     BackBuilder\Form\Builder
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class GlobalFormBuilder extends AFormBuilder 
{
    const FORM_PARAMETER = 'form';
    const TYPE_PARAMETER = 'type';
    
    /**
     * Global form builder constructor
     * 
     * @param \BackBuilder\Renderer\Renderer $renderer
     */
    public function __construct(Renderer $renderer) 
    {
        parent::__construct($renderer);
    }
    
    /**
     * Create form with config and value if in request
     * and return html
     */
    public function createForm() 
    {
        $request = $this->renderer->getApplication()->getRequest();
        $data_request = $request->request->all();
        $form = new Form($this->renderer, $this->config);
        foreach ($this->config as $key => $data) {
            if (true === isset($data[self::FORM_PARAMETER])) {
                $cConfig = $data[self::FORM_PARAMETER];
                if (null !== $classname = $this->getElementClassname($cConfig[self::TYPE_PARAMETER])) {
                    $value = null;
                    if (true === isset($data_request[$key])) {
                        $value = $data_request[$key];
                    }
                    $item = new $classname($key, $cConfig, $value);
                    $form->setItem($key, $item);
                }
            }
        }
       
        return $this->renderer->assign('form', $form)->partial($form->getTemplate());
    }
}
