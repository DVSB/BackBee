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

use BackBuilder\ClassContent\AClassContent;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @subpackage  Helper
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class mainnodeuri extends AHelper
{

    public function __invoke(AClassContent $content = null)
    {
        if (null === $content)
            $content = $this->_renderer->getObject();
        if (is_a($content, 'BackBuilder\ClassContent\AClassContent')) {
            $page = $content->getMainNode();
            if (null !== $page) {
                $base_url = '';

                $application = $this->getRenderer()->getApplication();
                if ($application->getSite()->getUid() !== $page->getSite()->getUid()) {
                    $base_url = $application->getRequest()->getScheme() . '://' . $page->getSite()->getServerName();
                }

                return $this->_renderer->getUri($base_url . $page->getUrl());
            }
        }

        return '#';
    }

}