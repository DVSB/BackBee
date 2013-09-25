<?php
namespace BackBuilder\MetaData;

use BackBuilder\NestedNode\Page;

/**
 * @category    BackBuilder
 * @package     BackBuilder\MetaData
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class MetaDataBag implements \IteratorAggregate, \Countable
{
    private $_metadatas;
    
    public function __construct(array $definitions = null, Page $page = null)
    {
        $content = (null === $page) ? null : $page->getContentSet();
        
        if (null !== $definitions) {
            foreach($definitions as $name => $definition) {
                if (false === is_array($definition)) continue;

                $metadata = new MetaData($name);                
                foreach($definition as $attrname => $attrvalue) {
                    if (false === is_array($attrvalue)) {
                        $metadata->setAttribute($attrname, $attrvalue, $content);
                        continue;
                    }
                    
                    if (true === array_key_exists('default', $attrvalue)) {
                        $metadata->setAttribute($attrname, $attrvalue['default'], $content);
                    }
                    
                    if (null !== $page && true === array_key_exists('layout', $attrvalue)) {
                        $layout_uid = $page->getLayout()->getUid();
                        if (true === array_key_exists($layout_uid, $attrvalue['layout'])) {
                            $metadata->setAttribute($attrname, $attrvalue['layout'][$layout_uid], $content);
                        }
                    }
                }
                
                if (0 < $metadata->count()) $this->add($metadata);
            }
        }        
    }
    
    public function compute(Page $page = null)
    {
        if (null === $page) return $this;
        
        foreach($this->_metadatas as $metadata) {
            $metadata->computeAttributes($page->getContentSet());
        }
        
        return clone $this;
    }
    
    public function add(MetaData $metadata)
    {
        $this->_metadatas[$metadata->getName()] = $metadata;
        return $this;
    }
    
    public function has($name)
    {
        return array_key_exists($name, $this->_metadatas);
    }
    
    public function get($name)
    {
        return (true === $this->has($name)) ? $this->_metadatas[$name] : null;
    }
    
    public function toArray()
    {
        $metadata = array();
        if (is_array($this->_metadatas)) {
            foreach($this->_metadatas as $meta) {
                $metadata[$meta->getName()] = $meta->toArray();
            }
        }
        
        return $metadata;
    }
    
    public function fromStdClass(\stdClass $object)
    {
        foreach(get_object_vars($object) as $name => $metadata) {
            if ($this->has($name)) {
                foreach($metadata as $attribute) {
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
     */
    public function count()
    {
        return count($this->_metadatas);
    }

    /**
     * Returns an iterator for attributes.
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_metadatas);
    }
}