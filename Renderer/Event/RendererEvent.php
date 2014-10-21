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

namespace BackBuilder\Renderer\Event;

use BackBuilder\Event\Event;

/**
 * A generic class of event in BB application
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class RendererEvent extends Event
{
    /**
     * [$renderer description]
     *
     * @var [type]
     */
    private $renderer;

    /**
     * [$render description]
     *
     * @var [type]
     */
    private $render;

    /**
     * [__construct description]
     * @param ARenderer $renderer [description]
     * @param [type]    $render   [description]
     */
    public function __construct($target, $arguments = null)
    {
        parent::__construct($target, $arguments);

        $this->render = null;
        if (is_array($arguments)) {
            $this->renderer = &$arguments[0];
            $this->render = &$arguments[1];
        } else {
            $this->renderer = &$arguments;
        }
    }

    /**
     * Getter of current event renderer object
     *
     * @return [type] [description]
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * Returns render string if it is setted, else null
     *
     * @return string|null
     */
    public function getRender()
    {
        return $this->render;
    }
}
