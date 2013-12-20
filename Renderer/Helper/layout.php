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

namespace BackBuilder\Renderer\Helper;

use BackBuilder\Site\Layout as EmptyLayout;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @subpackage  Helper
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class layout extends AHelper
{

    protected $_layout;

    public function getLayout()
    {
        if (null === $this->_layout) {
            $object = $this->_renderer->getObject();
            if (is_object($object) && method_exists($object, 'getLayout')) {
                $layout = $object->getLayout();
                if (is_a($layout, '\BackBuilder\Site\Layout'))
                    $this->_layout = $layout;
            }

            if (null === $this->_layout)
                $this->_layout = new EmptyLayout();
        }

        return $this->_layout;
    }

    public function __invoke()
    {
        return $this->getLayout();
    }

}