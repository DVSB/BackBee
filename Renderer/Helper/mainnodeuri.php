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

use BackBee\ClassContent\AbstractClassContent;

/**
 * Helper to get the main node uri from a content
 * @category    BackBee
 * @package     BackBee\Renderer
 * @subpackage  Helper
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class mainnodeuri extends AbstractHelper
{
    /**
     * Returns the main node uri form content if found '#' otherwise
     * @param  \BackBee\ClassContent\AbstractClassContent $content
     * @return string
     */
    public function __invoke(AbstractClassContent $content = null)
    {
        if (null === $content) {
            $content = $this->_renderer->getObject();
        }

        if ($content instanceof AbstractClassContent) {
            $page = $content->getMainNode();
            if (null !== $page) {
                return $this->_renderer->getUri($page->getUrl(), null, $page->getSite());
            }
        }

        return '#';
    }
}
