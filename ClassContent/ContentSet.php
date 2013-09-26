<?php

namespace BackBuilder\ClassContent;

use BackBuilder\NestedNode\Page,
    BackBuilder\ClassContent\Exception\UnknownPropertyException;

/**
 * A set of content objects in BackBuilder
 * Implements Iterator, Countable
 *
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 * @Entity(repositoryClass="BackBuilder\ClassContent\Repository\ClassContentRepository")
 * @Table(name="content")
 * @HasLifecycleCallbacks
 */
class ContentSet extends AClassContent implements \Iterator, \Countable
{

    /**
     * Internal position in iterator
     * @var int
     */
    protected $_index = 0;

    /**
     * Pages owning this contentset
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="BackBuilder\NestedNode\Page", mappedBy="_contentset")
     */
    protected $_pages;

    /**
     * Initialized datas on postLoad doctrine event
     */
    public function postLoad()
    {
        // Ensure class content are known
        $datas = (array) $this->_data;
        foreach ($datas as $data) {
            $type = @array_pop(array_flip($data));
            if (0 === strpos($type, 'BackBuilder\ClassContent')) {
                class_exists($type);
            }
        }

        parent::postLoad();
    }

    /**
     * Alternative recursive clone method, created because of problems related to doctrine clone method
     * @param \BackBuilder\NestedNode\Page $origin_page
     * @return \BackBuilder\ClassContent\ContentSet
     */
    public function createClone(Page $origin_page = null)
    {
        $clone = parent::createClone($origin_page);

        $zones = array();
        $mainnode_uid = null;

        if (null !== $origin_page) {
            $mainnode_uid = $origin_page->getUid();
            if ($origin_page->getContentSet()->getUid() === $this->getUid()) {
                $zones = $origin_page->getLayout()->getZones();
            }
        }

        foreach ($this as $subcontent) {
            if (!is_null($subcontent)) {
                if ($this->getProperty('clonemode') === 'none'
                        || ($this->key() < count($zones) && $zones[$this->key()]->defaultClassContent === 'inherited')
                        || (null !== $subcontent->getMainNode() && $subcontent->getMainNode()->getUid() !== $mainnode_uid)
                ) {
                    $clone->push($subcontent);
                } else {
                    $new_subcontent = $subcontent->createClone($origin_page);
                    $clone->push($new_subcontent);
                }
            }
        }

        return $clone;
    }

    /**
     * Sets options at the construction of a new instance
     * @param array $options Initial options for the content:
     *                         - label       the label of the content
     *                         - maxentry    the maximum number of content accepted
     *                         - minentry    the minimum number of content accepted
     *                         - accept      an array of classname accepted
     *                         - default     array default value for datas
     * @return \BackBuilder\ClassContent\ContentSet
     */
    protected function _setOptions($options = null)
    {
        if (null !== $options) {
            $options = (array) $options;
            if (true === array_key_exists('label', $options)) {
                $this->_label = $options['label'];
            }

            if (true === array_key_exists('maxentry', $options)) {
                $this->_maxentry = intval($options['maxentry']);
            }

            if (true === array_key_exists('minentry', $options)) {
                $this->_minentry = intval($options['minentry']);
            }

            if (true === array_key_exists('accept', $options)) {
                $this->_accept = (array) $options['accept'];
            }

            if (true === array_key_exists('default', $options)) {
                $options['default'] = (array) $options['default'];
                foreach ($options['default'] as $value) {
                    $this->push($value);
                }
            }
        }

        return $this;
    }

    /**
     * Dynamically adds and sets new element to this content
     * @param string $var the name of the element
     * @param string $type the type
     * @param array $options Initial options for the content (see this constructor)
     * @param Boolean $updateAccept dynamically accept or not the type for the new element
     * @return \BackBuilder\ClassContent\AClassContent The current instance
     */
    protected function _defineData($var, $type = 'scalar', $options = NULL, $updateAccept = FALSE)
    {
        if (true === $updateAccept) {
            $this->_addAcceptedType($type, $var);
        }

        if (null !== $options) {
            $options = (array) $options;
            if (true === array_key_exists('default', $options)) {
                $options['default'] = (array) $options['default'];
                foreach ($options['default'] as $value) {
                    $this->push($value);
                }
            }
        }

        return $this;
    }

    /**
     * Adds a new accepted type to the element
     * @param string $type the type to accept
     * @param string $var the element
     * @return \BackBuilder\ClassContent\AClassContent The current instance
     */
    protected function _addAcceptedType($type, $var = null)
    {
        $types = (array) $type;
        foreach ($types as $type) {
            $type = (NAMESPACE_SEPARATOR === substr($type, 0, 1)) ? substr($type, 1) : $type;
            if (false === in_array($type, $this->_accept)) {
                $this->_accept[] = $type;
            }
        }

        return $this;
    }

    /**
     * Empty the current set of contents
     */
    public function clear()
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->clear();

        $this->_subcontent->clear();
        $this->_subcontentmap = array();
        $this->_data = array();
        $this->_index = 0;
    }

    /**
     * @see Countable::count()
     */
    public function count()
    {
        return (NULL === $this->getDraft()) ? count($this->_data) : $this->getDraft()->count();
    }

    /**
     * @see Iterator::current()
     */
    public function current()
    {
        return (NULL === $this->getDraft()) ? $this->getData($this->_index) : $this->getDraft()->current();
    }

    /**
     * Return the first subcontent of the set
     * @return AClassContent the first element
     */
    public function first()
    {
        return $this->getData(0);
    }

    /**
     * Searches for a given element and, if found, returns the corresponding key/index
     * of that element. The comparison of two elements is strict, that means not
     * only the value but also the type must match.
     * For objects this means reference equality.
     *
     * @param mixed $element The element to search for.
     * @return mixed The key/index of the element or FALSE if the element was not found.
     */
    public function indexOf($element, $useIntIndex = false)
    {
        if ($element instanceof AClassContent) {
            $useIntIndex = (is_bool($useIntIndex)) ? $useIntIndex : false;
            if (FALSE !== $key = $this->_subcontent->indexOf($element)) {
                foreach ($this->_data as $key => $data) {
                    if (FALSE !== $index = array_search($element->getUid(), $data, true)) {
                        $index = ($useIntIndex) ? $key : $index;
                        return $index;
                    }
                }
            }

            return FALSE;
        }

        return array_search($element, $this->_data, true);
    }

    public function indexOfByUid($element, $useIntIndex = false)
    {
        if ($element instanceof AClassContent) {
            /* find content */
            $index = 0;
            foreach ($this->getData() as $key => $content) {
                if ($content instanceof AClassContent && $element->getUid() === $content->getUid()) {
                    $index = ($useIntIndex) ? $key : $index;
                    return $index;
                }
                $index++;
            }
            return FALSE;
        }
        return array_search($element, $this->_data, true);
    }

    /**
     * @param int $index
     * @param \BackBuilder\ClassContent\AClassContent $contentSet
     */
    public function replaceChildAtBy($index, AClassContent $contentSet)
    {
        $index = (isset($index) && is_int($index)) ? $index : false;
        if (is_bool($index))
            throw new \BackBuilder\Exception\BBException(__METHOD__ . " index  parameter must be an integer");
        $newContentsetArr = array();
        /** revoir * */
        foreach ($this->getData() as $key => $content) {
            $contentToAdd = ($key == $index) ? $contentSet : $content;
            $newContentsetArr[] = $contentToAdd;
        }

        $this->clear();
        foreach ($newContentsetArr as $key => $content) {
            $this->push($content);
        }
        return true;
    }

    /**
     * @param \BackBuilder\ClassContent\AClassContent $prevContentSet
     * @param \BackBuilder\ClassContent\AClassContent $nextContentSet
     * Replace prevContentSet by nextContentSet
     */
    public function replaceChildBy(AClassContent $prevContentSet, AClassContent $nextContentSet)
    {
        $index = $this->indexOfByUid($prevContentSet, true);
        if (is_bool($index))
            return false;
        return $this->replaceChildAtBy($index, $nextContentSet);
    }

    /**
     * Return the item at index
     * @param int $index
     * @return the item or NULL if $index is out of bounds
     */
    public function item($index)
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->item($index);

        if (0 <= $index && $index < $this->count())
            return $this->getData($index);

        return NULL;
    }

    /**
     * @see Iterator::key()
     */
    public function key()
    {
        return (NULL === $this->getDraft()) ? $this->_index : $this->getDraft()->key();
    }

    /**
     * Return the last subcontent of the set
     * @return AClassContent the last element
     */
    public function last()
    {
        return (NULL === $this->getDraft()) ? $this->getData($this->count() - 1) : $this->getDraft()->last();
    }

    /**
     * @see Iterator::next()
     */
    public function next()
    {
        return (NULL === $this->getDraft()) ? $this->getData($this->_index++) : $this->getDraft()->next();
    }

    /**
     * Pop the content off the end of the set and return it
     * @return AClassContent Returns the last content or NULL if set is empty
     */
    public function pop()
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->pop();

        $last = $this->last();

        if (NULL === $last)
            return NULL;

        array_pop($this->_data);
        $content = NULL;
        if (isset($this->_subcontentmap[$last->getUid()])) {
            $content = $this->_subcontent->get($this->_subcontentmap[$last->getUid()]);
            $this->_subcontent->removeElement($content);
            unset($this->_subcontentmap[$last->getUid()]);
        }

        $this->rewind();

        return $content;
    }

    /**
     * Push one element onto the end of the set
     * @param AClassContent $var The pushed values
     * @return ContentSet The current content set
     */
    public function push(AClassContent $var)
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->push($var);
        if ($this->_isAccepted($var)) {
            if (
                    (!$this->_maxentry && !$this->_minentry) ||
                    (is_array($this->_maxentry) && is_array($this->_minentry) && 0 == count($this->_maxentry)) ||
                    ($this->_maxentry > $this->count() && $this->_minentry < $this->count())
            ) {
                $this->_data[] = array($this->_getType($var) => $var->getUid());
                if ($this->_subcontent->add($var))
                    $this->_subcontentmap[$var->getUid()] = $this->_subcontent->indexOf($var);
            }
        }

        return $this;
    }

    /**
     * @see Iterator::rewind()
     */
    public function rewind()
    {
        if (NULL === $this->getDraft())
            $this->_index = 0;
        else
            $this->getDraft()->rewind();
    }

    /**
     * Shift the content off the beginning of the set and return it
     * @return AClassContent Returns the shifted content or NULL if set is empty
     */
    public function shift()
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->shift();

        $first = $this->first();
        if (NULL === $first)
            return NULL;

        array_shift($this->_data);
        $content = NULL;
        if (isset($this->_subcontentmap[$first->getUid()])) {
            $content = $this->_subcontent->get($this->_subcontentmap[$first->getUid()]);
            $this->_subcontent->removeElement($content);
            unset($this->_subcontentmap[$first->getUid()]);
        }

        $this->rewind();

        return $content;
    }

    /**
     * Prepend one to the beginning of the set
     * @param AClassContent $var The prepended values
     * @return ContentSet The current content set
     */
    public function unshift(AClassContent $var)
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->unshift($var);

        if ($this->_isAccepted($var)) {
            if (!$this->_maxentry || $this->_maxentry > $this->count()) {
                array_unshift($this->_data, array($this->_getType($var) => $var->getUid()));
                if ($this->_subcontent->add($var))
                    $this->_subcontentmap[$var->getUid()] = $this->_subcontent->indexOf($var);
            }
        }

        return $this;
    }

    /**
     * @see Iterator::valid()
     */
    public function valid()
    {
        return (NULL === $this->getDraft()) ? isset($this->_data[$this->_index]) : $this->getDraft()->valid();
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                   Implementation of IRenderable                        */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Return the data of this content
     * @param $var string The element to be return, if NULL, all datas are returned
     * @param $forceArray Boolean Force the return as array
     * @return mixed Could be either NULL or one or array of scalar, array, AClassContent instance
     * @throws \BackBuilder\AutoLoader\Exception\ClassNotFoundException Occurs if the class of a subcontent can not be loaded
     */
    public function getData($var = null, $forceArray = false)
    {
        try {
            return parent::getData($var, $forceArray);
        } catch (UnknownPropertyException $e) {
            return null;
        }
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                   Implementation of Serializable                       */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Initialized the instance from a serialized string
     * @param string $serialized
     * @param Boolean $strict If TRUE, all missing or additionnal element will generate an error
     * @return \BackBuilder\ClassContent\AClassContent The current instance
     * @throws \BackBuilder\ClassContent\Exception\UnknownPropertyException Occurs, in strict mode, when a 
     *                                                                      property does not match an element
     */
    public function unserialize($serialized, $strict = false)
    {
        if (false === is_object($serialized)) {
            $serialized = json_decode($serialized);
        }

        foreach (get_object_vars($serialized) as $property => $value) {
            $property = '_' . $property;

            if (true === in_array($property, array('_created', '_modified'))
                    || true === property_exists($this, $property)
                    || null === $value) {
                continue;
            } else if ("_param" === $property) {
                foreach ($value as $param => $paramvalue) {
                    $this->setParam($param, $paramvalue);
                }
            } else if ("_data" === $property) {
                $this->clear();
                foreach ($value as $val) {
                    $this->push($val);
                }
            } else if (true === $strict) {
                throw new Exception\UnknownPropertyException(sprintf('Unknown property `%s` in %s.', $property, ClassUtils::getRealClass($this->_getContentInstance())));
            }
        }

        return $this;
    }

}