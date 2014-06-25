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

use BackBuilder\NestedNode\Page;

/**
 * A set of metadata
 * 
 * @category    BackBuilder
 * @package     BackBuilder\MetaData
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MetaDataBag implements \IteratorAggregate, \Countable
{

    /**
     * The array of metadata
     * @var array
     */
    private $_metadatas = array();

    /**
     * Class constructor
     * @param array $definitions
     * @param \BackBuilder\NestedNode\Page $page
     * @codeCoverageIgnore
     */
    public function __construct(array $definitions = null, Page $page = null)
    {
        $this->_metadatas = array();
        $this->update($definitions, $page);
    }

    /**
     * Compute all metadata according to the provided page
     * @param \BackBuilder\NestedNode\Page $page
     * @return \BackBuilder\MetaData\MetaDataBag
     */
    public function compute(Page $page = null)
    {
        if (null === $page) {
            return $this;
        }

        foreach ($this->_metadatas as $metadata) {
            $metadata->computeAttributes($page->getContentSet(), $page);
        }

        return clone $this;
    }

    /**
     * Updates the associated definition of the set of metadata
     * @param array $definitions
     * @param \BackBuilder\NestedNode\Page $page
     */
    public function update(array $definitions = null, Page $page = null)
    {
        $content = (null === $page) ? null : $page->getContentSet();

        if (null !== $definitions) {
            foreach ($definitions as $name => $definition) {
                if (false === is_array($definition)) {
                    continue;
                }

                if (null === $metadata = $this->get($name)) {
                    $metadata = new MetaData($name);
                    $this->add($metadata);
                }

                foreach ($definition as $attrname => $attrvalue) {
                    if (false === is_array($attrvalue)) {
                        $attrvalue = ('' === $metadata->getAttribute($attrname)) ? $attrvalue : $metadata->getAttribute($attrname);
                        $metadata->setAttribute($attrname, $attrvalue, $content);
                        continue;
                    }

                    if (true === $metadata->hasAttribute($attrname)) {
                        if (null !== $page && true === array_key_exists('layout', $attrvalue)) {
                            $layout_uid = $page->getLayout()->getUid();
                            if (true === array_key_exists($layout_uid, $attrvalue['layout'])) {
                                $scheme = (is_array($attrvalue['layout'][$layout_uid])) ? reset($attrvalue['layout'][$layout_uid]) : $attrvalue['layout'][$layout_uid];
                                $metadata->updateAttributeScheme($attrname, $scheme, $content);
                            }
                        }

                        continue;
                    }

                    if (true === array_key_exists('default', $attrvalue)) {
                        $value = (is_array($attrvalue['default'])) ? reset($attrvalue['default']) : $attrvalue['default'];
                        $metadata->setAttribute($attrname, $value, $content);
                    }

                    if (null !== $page && true === array_key_exists('layout', $attrvalue)) {
                        $layout_uid = $page->getLayout()->getUid();
                        if (true === array_key_exists($layout_uid, $attrvalue['layout'])) {
                            $value = (is_array($attrvalue['layout'][$layout_uid])) ? reset($attrvalue['layout'][$layout_uid]) : $attrvalue['layout'][$layout_uid];
                            $metadata->setAttribute($attrname, $value, $content);
                        }
                    }
                }
            }
        }
    }

    /**
     * Adds a new matadata to the bag
     * @param \BackBuilder\MetaData\MetaData $metadata
     * @return \BackBuilder\MetaData\MetaDataBag
     * @codeCoverageIgnore
     */
    public function add(MetaData $metadata)
    {
        $this->_metadatas[$metadata->getName()] = $metadata;
        return $this;
    }

    /**
     * Checks if a metadata exists with the given name
     * @param string $name
     * @return Boolean
     */
    public function has($name)
    {
        return array_key_exists($name, $this->_metadatas);
    }

    /**
     * Returns the metadata associated to $name or NULL if it doesn't exist
     * @param string $name
     * @return \BackBuilder\MetaData\MetaData|NULL
     */
    public function get($name)
    {
        return (true === $this->has($name)) ? $this->_metadatas[$name] : null;
    }

    /**
     * An array representation of the bag
     * @return array
     * @codeCoverageIgnore
     */
    public function toArray()
    {
        $metadata = array();
        if (is_array($this->_metadatas)) {
            foreach ($this->_metadatas as $meta) {
                $metadata[$meta->getName()] = $meta->toArray();
            }
        }

        return $metadata;
    }

    /**
     * @param \stdClass $object
     * @return \BackBuilder\MetaData\MetaDataBag
     */
    public function fromStdClass(\stdClass $object)
    {
        foreach (get_object_vars($object) as $name => $metadata) {
            if ($this->has($name)) {
                foreach ($metadata as $attribute) {
                    if (false === $attribute->iscomputed) {
                        $this->get($name)->setAttribute($attribute->attr, $attribute->value);
                    }
                }
            }
        }

        return clone $this;
    }

    /**
     * Returns the number of attributes.
     * @return int
     * @codeCoverageIgnore
     */
    public function count()
    {
        return count($this->_metadatas);
    }

    /**
     * Returns an iterator for attributes.
     * @return \ArrayIterator
     * @codeCoverageIgnore
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_metadatas);
    }

}
