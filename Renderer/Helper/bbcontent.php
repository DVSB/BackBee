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
 * Helper providing HTML attributes to online-edited content
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @subpackage  Helper
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class bbcontent extends AHelper
{
    /**
     * Return HTML formatted attribute for provided content
     * @param  AClassContent $content the content we want to generate its HTML attribute; if $content is null, we
     *                                get the current object setted on current renderer
     * @return string
     */
    public function __invoke(AClassContent $content = null)
    {
        $result = '';
        $content = $content?: $this->getRenderer()->getObject();
        if (null !== $this->getRenderer()->getApplication()->getBBUserToken()) {
            $datas = $content->jsonSerialize();
            $result = 'data-bb-identifier="' . str_replace('\\', '/', $datas['type']) . '(' . $datas['uid'] . ')"';
        }

        return $result;
    }
}
