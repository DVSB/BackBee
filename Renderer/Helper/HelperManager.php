<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Renderer\Helper;

use Symfony\Component\HttpFoundation\ParameterBag;

use BackBee\Renderer\ARenderer;

class HelperManager
{
    /**
     * [$renderer description]
     * @var [type]
     */
    private $renderer;

    /**
     * [$bbapp description]
     * @var [type]
     */
    private $bbapp;

    /**
     * [$helpers description]
     * @var [type]
     */
    private $helpers;

    /**
     * [__construct description]
     * @param ARenderer $renderer [description]
     */
    public function __construct(ARenderer $renderer)
    {
        $this->renderer = $renderer;
        $this->bbapp = $this->renderer->getApplication();
        $this->helpers = new ParameterBag();
    }

    /**
     * [get description]
     * @param  [type] $method [description]
     * @return [type] [description]
     */
    public function get($method)
    {
        $helper = null;
        if (true === $this->helpers->has($method)) {
            $helper = $this->helpers->get($method);
        }

        return $helper;
    }

    /**
     * [create description]
     * @param  [type] $method [description]
     * @param  [type] $argv   [description]
     * @return [type] [description]
     */
    public function create($method, $argv)
    {
        $helperClass = '\BackBee\Renderer\Helper\\'.$method;
        if (true === class_exists($helperClass)) {
            $this->helpers->set($method, new $helperClass($this->renderer, $argv));
        }

        return $this->helpers->get($method);
    }

    /**
     * [updateRenderer description]
     * @param  ARenderer $renderer [description]
     * @return [type]    [description]
     */
    public function updateRenderer(ARenderer $renderer)
    {
        $this->renderer = $renderer;
        foreach ($this->helpers->all() as $h) {
            $h->setRenderer($renderer);
        }
    }
}
