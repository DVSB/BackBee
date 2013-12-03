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

namespace BackBuilder\MetaData;

use BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\ContentSet;

/**
 * A metadata
 * 
 * Metadata instance is composed by a name and a set of key/value attributes
 * The attribute can be staticaly defined in yaml file or to be computed:
 * 
 *     description:
 *       name: 'description'
 *       content:
 *         default: "Default value"
 *         layout:
 *           f5da92419743370d7581089605cdbc6e: $ContentSet[0]->$actu[0]->$chapo
 *       lang: 'en'
 * 
 * In this example, the attribute `lang` is static and set to `fr`, the attribute 
 * `content` will be set to `Default value`:
 *     <meta name="description" content="Default value" lang="en">
 * 
 * But if the page has the layout `f5da92419743370d7581089605cdbc6e` the attribute
 * `content` will set according to the scheme:
 * value of the element `chapo` of the first `content `actu` in the first column.
 * 
 * @category    BackBuilder
 * @package     BackBuilder\MetaData
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MetaData implements \IteratorAggregate, \Countable
{

    /**
     * The name of the metadata
     * @var string
     */
    private $_name;

    /**
     * An array of attributes
     * @var array 
     */
    private $_attributes;

    /**
     * The scheme to compute for dynamic attributes
     * @var array 
     */
    private $_scheme;

    /**
     * The attributes to be computed
     * @var array 
     */
    private $_isComputed;

    /**
     * Class constructor
     * @param string $name
     */
    public function __construct($name = null)
    {
        if (null !== $name)
            $this->setName($name);

        $this->_attributes = array();
        $this->_scheme = array();
        $this->_isComputed = array();
    }

    /**
     * Retuns the name of the metadata
     * @return string
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets the name of the metadata
     * @param string $name
     * @return \BackBuilder\MetaData\MetaData
     * @throws \BackBuilder\Exception\BBException Occurs if $name if not a valid string
     */
    public function setName($name)
    {
        if (false === preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $name)) {
            throw new \BackBuilder\Exception\InvalidArgumentException('Invalid name for metadata: \'%s\'', $name);
        }

        $this->_name = $name;
        return $this;
    }

    /**
     * Checks if the provided attribute exists for this metadata
     * @param string $attribute The attribute looked for
     * @return Boolean Returns TRUE if the attribute is defined for the metadata, FALSE otherwise
     * @codeCoverageIgnore
     */
    public function hasAttribute($attribute)
    {
        return array_key_exists($attribute, $this->_attributes);
    }

    /**
     * Returns the value of the attribute
     * @param string $attribute The attribute looked for
     * @param string $default Optional, the default value return if attribute does not exist
     * @return string
     */
    public function getAttribute($attribute, $default = '')
    {
        return (true === $this->hasAttribute($attribute)) ? $this->_attributes[$attribute] : $default;
    }

    /**
     * Sets the value of the attribute
     * @param string $attribute
     * @param string $value
     * @param \BackBuilder\ClassContent\AClassContent $content Optional, if the attribute is computed
     *                                                         the content on which apply the scheme
     * @return \BackBuilder\MetaData\MetaData
     */
    public function setAttribute($attribute, $value, AClassContent $content = null)
    {
        if (0 < preg_match('/^(\$([a-z\/\\\\]+)(\[([0-9]+)\]){0,1}(->){0,1})+$/i', $value)) {
            $this->_scheme[$attribute] = $value;
            $this->_isComputed[$attribute] = true;

            if (null !== $content) {
                $this->computeAttributes($content);
            }
        } else {
            $this->_attributes[$attribute] = $value;
            $this->_isComputed[$attribute] = false;
        }

        return $this;
    }

    /**
     * Compute values of attributes according to the AClassContent provided
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return \BackBuilder\MetaData\MetaData
     */
    public function computeAttributes(AClassContent $content)
    {
        foreach ($this->_attributes as $attribute => $value) {
            if (true === $this->_isComputed[$attribute]
                    && true === array_key_exists($attribute, $this->_scheme)) {
                try {
                    foreach (explode('->', $this->_scheme[$attribute]) as $scheme) {
                        $draft = null;
                        if (true === is_object($content)) {
                            if (null !== $draft = $content->getDraft())
                                $content->releaseDraft();
                        }
                        $newcontent = $content;
                        $matches = array();
                        if (preg_match('/\$([a-z\/]+)(\[([0-9]+)\]){0,1}/i', $scheme, $matches)) {
                            if (3 < count($matches) && $content instanceof ContentSet && 'ContentSet' === $matches[1]) {
                                $newcontent = $content->item($matches[3]);
                            } elseif (3 < count($matches) && $content instanceof ContentSet) {
                                $index = intval($matches[3]);
                                $classname = 'BackBuilder\ClassContent\\' . str_replace('/', NAMESPACE_SEPARATOR, $matches[1]);
                                foreach ($content as $subcontent) {
                                    if (get_class($subcontent) == $classname) {
                                        if (0 === $index) {
                                            $newcontent = $subcontent;
                                        } else {
                                            $index--;
                                        }
                                    }
                                }
                            } elseif (true === is_object($content) && 1 < count($matches)) {
                                $property = $matches[1];
                                $newcontent = $content->$property;
                            }
                        }

                        if (null !== $draft)
                            $content->setDraft($draft);
                        $content = $newcontent;
                    }

                    if ($content instanceof AClassContent && $content->isElementContent()) {
                        if (null !== $draft = $content->getDraft())
                            $content->releaseDraft();
                        $this->_attributes[$attribute] = strip_tags($content->__toString());
                        if (null !== $draft)
                            $content->setDraft($draft);
                    }
                } catch (\Exception $e) {
                    // Nothing to do
                }
            }
        }

        return $this;
    }

    /**
     * Returns a array representation of this metadata
     * @return array
     */
    public function toArray()
    {
        $attributes = array();
        foreach ($this->_attributes as $attribute => $value) {
            $attr = new \stdClass();
            $attr->attr = $attribute;
            $attr->value = $value;
            $attr->iscomputed = $this->_isComputed[$attribute];
            $attr->scheme = (true === array_key_exists($attribute, $this->_scheme)) ? $this->_scheme[$attribute] : null;
            $attributes[] = $attr;
        }

        return $attributes;
    }

    /**
     * Returns the number of attributes.
     * @return int
     */
    public function count()
    {
        return count($this->_attributes);
    }

    /**
     * Returns an iterator for attributes.
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_attributes);
    }

}