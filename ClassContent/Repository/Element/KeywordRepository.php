<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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

namespace BackBee\ClassContent\Repository\Element;

use BackBee\ClassContent\AClassContent;
use BackBee\ClassContent\Element\Keyword;
use BackBee\ClassContent\Exception\ClassContentException;
use BackBee\ClassContent\Repository\ClassContentRepository;

/**
 * keyword repository.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class KeywordRepository extends ClassContentRepository
{
    /**
     * Do stuf on update by post of the content editing form.
     *
     * @param \BackBee\ClassContent\AClassContent $content
     * @param stdClass                            $value
     * @param \BackBee\ClassContent\AClassContent $parent
     *
     * @return \BackBee\ClassContent\Element\File
     *
     * @throws ClassContentException Occures on invalid content type provided
     */
    public function getValueFromPost(AClassContent $content, $value, AClassContent $parent = null)
    {
        if (false === ($content instanceof Keyword)) {
            throw new ClassContentException('Invalid content type');
        }

        if (true === property_exists($value, 'value')) {
            $content->value = $value->value;

            if (null !== $realkeyword = $this->_em->find('BackBee\NestedNode\KeyWord', $value->value)) {
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
     * Do stuf removing content from the content editing form.
     *
     * @param \BackBee\ClassContent\AClassContent $content
     * @param type                                $value
     * @param \BackBee\ClassContent\AClassContent $parent
     *
     * @return type
     *
     * @throws ClassContentException
     */
    public function removeFromPost(AClassContent $content, $value = null, AClassContent $parent = null)
    {
        if (false === ($content instanceof Keyword)) {
            throw new ClassContentException('Invalid content type');
        }

        $content = parent::removeFromPost($content);

        if (true === property_exists($value, 'value')) {
            if (null === $parent) {
                throw new ClassContentException('Invalid parent content');
            }

            if (null !== $realkeyword = $this->_em->find('BackBee\NestedNode\KeyWord', $value->value)) {
                if (true === $realkeyword->getContent()->contains($parent)) {
                    $realkeyword->removeContent($parent);
                }
            }
        }

        return $content;
    }
}
