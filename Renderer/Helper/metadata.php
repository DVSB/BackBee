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

use BackBuilder\MetaData\MetaDataBag;

/**
 * Helper generating <META> tag for the page being rendered
 * if none available, the default metadata are generaed
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @subpackage  Helper
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class metadata extends AHelper
{

    public function __invoke()
    {
        if (null === $renderer = $this->_renderer) {
            return '';
        }

        if (null === $page = $renderer->getCurrentPage()) {
            return '';
        }

        if (null === $metadata = $page->getMetaData()) {
            $metadata = new MetaDataBag($renderer->getApplication()->getConfig()->getMetadataConfig(), $page);
        }

        $result = '';
        foreach ($metadata as $meta) {
            if (0 < $meta->count()) {
                $result .= '<meta ';
                foreach ($meta as $attribute => $value) {
                    if (false !== strpos($meta->getName(), 'keyword') && 'content' === $attribute) {
                        $keywords = explode(',', $value);
                        $objects = $this->getRenderer()->getKeywordObjects($keywords);
                        foreach($objects as $object) {
                            $value = trim(str_replace($object->getUid(), $object->getKeyWord(), $value), ',');
                        }
                    }
                    
                    $result .= $attribute . '="' . html_entity_decode($value, ENT_COMPAT, 'UTF-8') . '" ';
                }
                $result .= '/>';
            }
        }

        return $result;
    }

}