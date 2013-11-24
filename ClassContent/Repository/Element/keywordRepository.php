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

namespace BackBuilder\ClassContent\Repository\Element;

use BackBuilder\ClassContent\Repository\ClassContentRepository,
    BackBuilder\ClassContent\Exception\ClassContentException,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\Element\keyword as elementKeyword;

/**
 * keyword repository
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @subpackage  Repository\Element
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class keywordRepository extends ClassContentRepository
{

    /**
     * Do stuf on update by post of the content editing form
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param stdClass $value
     * @param \BackBuilder\ClassContent\AClassContent $parent
     * @return \BackBuilder\ClassContent\Element\file
     * @throws ClassContentException Occures on invalid content type provided
     */
    public function getValueFromPost(AClassContent $content, $value, AClassContent $parent = null)
    {
        if (false === ($content instanceof elementKeyword)) {
            throw new ClassContentException('Invalid content type');
        }

        if (true === property_exists($value, 'value')) {
            $content->value = $value->value;

            if (null !== $realkeyword = $this->_em->find('BackBuilder\NestedNode\KeyWord', $value->value)) {
                if (null === $parent) {
                    throw new ClassContentException('Invalid parent content');
                }

                if (null === $realkeyword->getContent() || false === $realkeyword->getContent()->contains($parent)) {
                    $realkeyword->addContent($parent);
                }
            }
        }

        return $content;
    }

    /**
     * Do stuf removing content from the content editing form
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param type $value
     * @param \BackBuilder\ClassContent\AClassContent $parent
     * @return type
     * @throws ClassContentException
     */
    public function removeFromPost(AClassContent $content, $value = null, AClassContent $parent = null)
    {
        if (false === ($content instanceof elementKeyword)) {
            throw new ClassContentException('Invalid content type');
        }

        $content = parent::removeFromPost($content);

        if (true === property_exists($value, 'value')) {
            if (null === $parent) {
                throw new ClassContentException('Invalid parent content');
            }

            if (null !== $realkeyword = $this->_em->find('BackBuilder\NestedNode\KeyWord', $value->value)) {
                if (true === $realkeyword->getContent()->contains($parent)) {
                    $realkeyword->removeContent($parent);
                }
            }
        }

        return $content;
    }

}