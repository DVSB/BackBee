<?php
namespace BackBuilder\MetaData;

use BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\ContentSet;

/**
 * @category    BackBuilder
 * @package     BackBuilder\MetaData
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class MetaData implements \IteratorAggregate, \Countable
{
    private $_name;
    private $_attributes;
    private $_scheme;
    private $_isComputed;

    public function __construct($name = null)
    {
        if (null !== $name) $this->setName($name);

        $this->_attributes = array();
        $this->_scheme = array();
        $this->_isComputed = array();
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        if (false === preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $name)) {
            throw new \BackBuilder\Exception\BBException(sprintf('Invalid name for metadata: \'%s\'', $name));            
        }
        
        $this->_name = $name;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param string $attribute
     * @return boolean
     */
    public function hasAttribute($attribute)
    {
        return array_key_exists($attribute, $this->_attributes);
    }

    public function getAttribute($attribute, $default = '')
    {
        return (true === $this->hasAttribute($attribute)) ? $this->_attributes[$attribute] : $default;
    }

    public function setAttribute($attribute, $value, AClassContent $content = null)
    {
        if (0 < preg_match('/^(\$([a-z\/\\\\]+)(\[([0-9]+)\]){0,1}(->){0,1})+$/i', $value)) {
            $this->_scheme[$attribute] = $value;
            $this->_isComputed[$attribute] = true;
            
            if (null !== $content) $this->computeAttributes($content);
        } else {
            $this->_attributes[$attribute] = $value;
            $this->_isComputed[$attribute] = false;
        }
        
        return $this;
    }
    
    public function computeAttributes(AClassContent $content)
    {
        foreach($this->_attributes as $attribute => $value) {
            if (true === $this->_isComputed[$attribute]
                    && true === array_key_exists($attribute, $this->_scheme)) {
                try {
                    foreach(explode('->', $this->_scheme[$attribute]) as $scheme) {
                        $draft = null;
                        if (true === is_object($content)) {
                            if (null !== $draft = $content->getDraft()) $content->releaseDraft();                            
                        }
                        $newcontent = $content;
                        $matches = array();
                        if (preg_match('/\$([a-z\/]+)(\[([0-9]+)\]){0,1}/i', $scheme, $matches)) { 
                            if (3 < count($matches) && $content instanceof ContentSet && 'ContentSet' === $matches[1]) {
                                $newcontent = $content->item($matches[3]);
                            } elseif (3 < count($matches) && $content instanceof ContentSet) {
                                $index = intval($matches[3]);
                                $classname = 'BackBuilder\ClassContent\\'.str_replace('/', NAMESPACE_SEPARATOR, $matches[1]);
                                foreach($content as $subcontent) {
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
                        
                        if (null !== $draft) $content->setDraft($draft);
                        $content = $newcontent;
                    }
                    
                    if ($content instanceof AClassContent && $content->isElementContent()) {
                        if (null !== $draft = $content->getDraft()) $content->releaseDraft();
                        $this->_attributes[$attribute] = strip_tags($content->__toString());
                        if (null !== $draft) $content->setDraft($draft);
                    }
                } catch (\Exception $e) {
                    // Nothing to do
                }            
            }
        }
        
        return $this;
    }
    
    public function toArray()
    {
        $attributes = array();
        foreach($this->_attributes as $attribute => $value) {
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
     * @codeCoverageIgnore
     * @return int
     */
    public function count()
    {
        return count($this->_attributes);
    }

    /**
     * Returns an iterator for attributes.
     * @codeCoverageIgnore
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_attributes);
    }
}