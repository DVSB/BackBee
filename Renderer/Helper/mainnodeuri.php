<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Renderer\Helper;

use BackBee\ClassContent\AClassContent;

/**
 * Helper to get the main node uri from a content
 * @category    BackBee
 * @package     BackBee\Renderer
 * @subpackage  Helper
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class mainnodeuri extends AHelper
{
    /**
     * Returns the main node uri form content if found '#' otherwise
     * @param  \BackBee\ClassContent\AClassContent $content
     * @return string
     */
    public function __invoke(AClassContent $content = null)
    {
        if (null === $content) {
            $content = $this->_renderer->getObject();
        }

        if ($content instanceof AClassContent) {
            $page = $content->getMainNode();
            if (null !== $page) {
                return $this->_renderer->getUri($page->getUrl(), null, $page->getSite());
            }
        }

        return '#';
    }
}
