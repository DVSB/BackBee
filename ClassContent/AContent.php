<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\ClassContent;

use Symfony\Component\Security\Core\Util\ClassUtils;

use BackBee\Exception\InvalidArgumentException;
use BackBee\Renderer\IRenderable;
use BackBee\Security\Acl\Domain\IObjectIdentifiable;
use BackBee\Util\Parameter;

/**
 * Abstract class for every content and its revisions in BackBee
 *
 * @category    BackBee
 * @package     BackBee\ClassContent
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @MappedSuperclass
 */
abstract class AContent implements IObjectIdentifiable, IRenderable, \JsonSerializable
{
    /**
     * BackBee's class content classname must be prefixed by this
     */
    const CLASSCONTENT_BASE_NAMESPACE = 'BackBee\ClassContent\\';

    /**
     * Unique identifier
     * @var string
     * @Id @Column(type="string", name="uid")
     */
    protected $_uid;

    /**
     * The label of this content
     * @var string
     * @Column(type="string", name="label", nullable=true)
     */
    protected $_label;

    /**
     * The acceptable class name for values
     * @var array
     * @Column(type="array", name="accept")
     */
    protected $_accept = array();

    /**
     * A map of content
     * @var mixed
     * @Column(type="array", name="data")
     */
    protected $_data = array();

    /**
     * The content's parameters
     * @var array
     * @Column(type="array", name="parameters")
     */
    protected $_parameters = array();

    /**
     * The maximal number of items for values
     * @var array
     * @Column(type="array", name="maxentry")
     */
    protected $_maxentry = array();

    /**
     * The minimal number of items for values
     * @var array
     * @Column(type="array", name="minentry")
     */
    protected $_minentry = array();

    /**
     * The creation datetime
     * @var \DateTime
     * @Column(type="datetime", name="created")
     */
    protected $_created;

    /**
     * The last modification datetime
     * @var \DateTime
     * @Column(type="datetime", name="modified")
     */
    protected $_modified;

    /**
     * Revision number
     * @var int
     * @Column(type="integer", name="revision")
     */
    protected $_revision;

    /**
     * The current state
     * @var int
     * @Column(type="integer", name="state")
     */
    protected $_state;

    /**
     * Class constructor
     * @param string $uid     The unique identifier
     * @param array  $options Initial options for the content:
     *                        - accept      array Acceptable class names for the value
     *                        - maxentry    int The maxentry in value
     *                        - default     array default value for datas
     */
    public function __construct($uid = null, $options = null)
    {
        $this->_uid = (is_null($uid)) ? md5(uniqid('', true)) : $uid;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();
        $this->_revision = 0;

        $this->_setOptions($options);
    }

    /**
     * Magical function to get value for given element
     * @param  string                                                   $var The name of the element
     * @return mixed                                                    The value
     * @throws \BackBee\ClassContent\Exception\UnknownPropertyException Occurs when $var does not match an element
     */
    public function __get($var)
    {
        if ($this->_getContentInstance() instanceof ContentSet) {
            throw new Exception\UnknownPropertyException(sprintf('Unknown property %s in %s.', $var, ClassUtils::getRealClass($this->_getContentInstance())));
        }

        return $this->getData($var);
    }

    /**
     * Magical function to set value to given element
     * @param  string                                                   $var   The name of the element
     * @param  mixed                                                    $value The value to set
     * @return \BackBee\ClassContent\AClassContent                      The current instance content
     * @throws \BackBee\ClassContent\Exception\UnknownPropertyException Occurs when $var does not match an element
     */
    public function __set($var, $value)
    {
        if ($this->_getContentInstance() instanceof ContentSet || false === isset($this->_data[$var])) {
            throw new Exception\UnknownPropertyException(sprintf('Unknown property %s in %s.', $var, ClassUtils::getRealClass($this->_getContentInstance())));
        }

        $values = (true === is_array($value)) ? $value : array($value);

        $this->__unset($var);
        $val = array();

        foreach ($values as $value) {
            if ((isset($this->_maxentry[$var]) && 0 < $this->_maxentry[$var] && $this->_maxentry[$var] == count($val)) || (isset($this->_minentry[$var]) && count($val) < $this->_minentry[$var] && $this->_maxentry[$var] == count($val))) {
                break;
            }

            if (true === $this->_isAccepted($value, $var)) {
                $type = $this->_getType($value);

                if (true === is_object($value) && $value instanceof AClassContent) {
                    $value = $this->_addSubcontent($value);
                }

                $val[] = array($type => $value);
            }
        }

        $this->_data[$var] = $val;
        $this->_modified = new \DateTime();

        return $this->_getContentInstance();
    }

    /**
     * Magical function to check the setting of an element
     * @param  string                                                   $var The name of the element
     * @return Boolean                                                  TRUE if an element is set for $var, FALSE otherwise
     * @throws \BackBee\ClassContent\Exception\UnknownPropertyException Occurs when $var does not match an element
     */
    public function __isset($var)
    {
        if ($this->_getContentInstance() instanceof ContentSet) {
            throw new Exception\UnknownPropertyException(sprintf('Unknown property %s in %s.', $var, ClassUtils::getRealClass($this->_getContentInstance())));
        }

        return true === array_key_exists($var, $this->_data) && 0 < count($this->_data[$var]);
    }

    /**
     * Magical function to unset an element
     * @param  string                                                   $var The name of the element to unset
     * @throws \BackBee\ClassContent\Exception\UnknownPropertyException Occurs when $var does not match an element
     */
    public function __unset($var)
    {
        if ($this->_getContentInstance() instanceof ContentSet) {
            throw new Exception\UnknownPropertyException(sprintf('Unknown property %s in %s.', $var, ClassUtils::getRealClass($this->_getContentInstance())));
        }

        if ($this->__isset($var)) {
            $this->_removeSubcontent($var);
            $this->_data[$var] = array();
        }
    }

    /**
     * Magical function to get a string representation of the content
     * @return string
     */
    public function __toString()
    {
        if (false === $this->isElementContent()) {
            return sprintf('%s(%s)', ClassUtils::getRealClass($this->_getContentInstance()), $this->getUid());
        }

        $string = '';
        foreach ($this->getData() as $val) {
            $string .= $val;
        }

        return $string;
    }

    /**
     * Returns the unique identifier
     * @return string
     * @codeCoverageIgnore
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Returns the label
     * @return string
     * @codeCoverageIgnore
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * Return the current accepted subcontents
     * @return array
     * @codeCoverageIgnore
     */
    public function getAccept()
    {
        return $this->_accept;
    }

    /**
     * Returns the raw datas array
     * @return array
     * @codeCoverageIgnore
     */
    public function getDataToObject()
    {
        return $this->_data;
    }

    /**
     * Gets the maxentry
     * @return array
     * @codeCoverageIgnore
     */
    public function getMaxEntry()
    {
        return $this->_maxentry;
    }

    /**
     * Gets the minentry
     * @return array
     * @codeCoverageIgnore
     */
    public function getMinEntry()
    {
        return $this->_minentry;
    }

    /**
     * Returns the creation date
     * @return DateTime
     * @codeCoverageIgnore
     */
    public function getCreated()
    {
        return $this->_created;
    }

    /**
     * Returns the last modified date
     * @return DateTime
     * @codeCoverageIgnore
     */
    public function getModified()
    {
        return $this->_modified;
    }

    /**
     * Returns the revision number
     * @return int
     * @codeCoverageIgnore
     */
    public function getRevision()
    {
        return $this->_revision;
    }

    /**
     * Returns the state
     * @return int
     * @codeCoverageIgnore
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * Sets the label
     * @param  string                         $label
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setLabel($label)
    {
        $this->_label = $label;

        return $this->_getContentInstance();
    }

    /**
     * Set the acceptable classname
     * @param  array                          $accept
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setAccept($accept)
    {
        $this->_accept = $accept;

        return $this->_getContentInstance();
    }

    /**
     * Sets one or all parameters
     * @param  string                         $var    the parameter name to set, if NULL all the parameters array wil be set
     * @param  mixed                          $values the parameter value or all the parameters if $var is NULL
     * @param  string                         $type   the optionnal casting type of the value
     * @return \BackBee\ClassContent\AContent The current instance
     */
    public function setParam($var = null, $values = null, $type = null)
    {
        if (null === $var) {
            $this->_parameters = $values;
        } else {
            $paths = explode(':', $var);
            if (1 < count($paths)) {
                $this->setParameterRecursively($paths, $values, $type);
            } else {
                if (null !== $values) {
                    if (null !== $type) {
                        $values = array($type => $values);
                    } elseif (false === is_array($values)) {
                        $values = array($values);
                    }

                    // A surveiller cette partie pour les revisions
                    if (true === is_array($this->_parameters) && true === array_key_exists($var, $this->_parameters) && true === is_array($this->_parameters[$var])) {
                        $values = array_replace_recursive($this->_parameters[$var], $values);
                    }
                }

                $this->_parameters[$var] = $values;
            }
        }

        return $this->_getContentInstance();
    }

    /**
     * Setters of parameters with the ':' syntax
     *
     * @param array  $paths
     * @param mixed  $values new value to set
     * @param string $type   type of the parameter, can be null
     */
    private function setParameterRecursively(array $paths, $value, $type)
    {
        if (null !== $type) {
            $path = array_shift($paths);
            array_unshift($paths, $type);
            array_unshift($paths, $path);
            unset($path);
        }

        $target = &$this->_parameters;
        foreach ($paths as $path) {
            if (false === array_key_exists($path, $target)) {
                throw new InvalidArgumentException(
                    'Invalid path provided for setting parameter value: '.implode(':', $paths)
                );
            }

            $target = &$target[$path];
        }

        $target = $value;
    }

    /**
     * Sets the maximum number of items for elements
     * @param  array                          $maxentry
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setMaxEntry(array $maxentry)
    {
        $this->_maxentry = $maxentry;

        return $this->_getContentInstance();
    }

    /**
     * Sets the minimum number of items for elements
     * @param  array                          $minentry
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setMinEntry(array $minentry = null)
    {
        $this->_minentry = $minentry;

        return $this->_getContentInstance();
    }

    /**
     * Sets creation date
     * @param  \DateTime                      $created Current date time by default
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setCreated(\DateTime $created = null)
    {
        $this->_created = (null === $created) ? new \DateTime() : $created;

        return $this->_getContentInstance();
    }

    /**
     * Sets the last modification date
     * @param  DateTime                       $modified Current date time by default
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setModified(\DateTime $modified = null)
    {
        $this->_modified = (null === $modified) ? new \DateTime() : $modified;

        return $this->_getContentInstance();
    }

    /**
     * Sets the revision number
     * @param  int                            $revision
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setRevision($revision)
    {
        $this->_revision = $revision;

        return $this->_getContentInstance();
    }

    /**
     * Sets the state
     * @param  int                            $state
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setState($state)
    {
        $this->_state = $state;

        return $this->_getContentInstance();
    }

    /**
     * Is this content is a primary content ?
     * @return Boolean TRUE if the content is a primary content
     * @codeCoverageIgnore
     */
    public function isElementContent()
    {
        return false !== strpos(ClassUtils::getRealClass($this->_getContentInstance()), 'BackBee\ClassContent\Element\\');
    }

    /**
     * Checks if the element accept subcontent
     * @param  string  $var the element
     * @return Boolean TRUE if a subcontents are accepted, FALSE otherwise
     */
    public function acceptSubcontent($var)
    {
        if (false === array_key_exists($var, $this->_accept)) {
            return false;
        }

        foreach ($this->_accept[$var] as $type) {
            if (0 === strpos($type, 'BackBee\ClassContent')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Initialized datas on postLoad doctrine event
     */
    public function postLoad()
    {
        if (null !== $this->_getContentInstance()) {
            $this->_parameters = Parameter::paramsReplaceRecursive($this->_getContentInstance()->getDefaultParameters(), $this->getParam());
        }
    }

    /**
     * Return a subcontent instance by its type and value, FALSE if not found
     * @param  string                                    $type  The classname of the subcontent
     * @param  string                                    $value The value of the subcontent (uid)
     * @return \BackBee\ClassContent\AClassContent|FALSE
     */
    protected function _getContentByDataValue($type, $value)
    {
        return new $type($value);
    }

    /**
     * Returns the content
     * @return \BackBee\ClassContent\AClassContent
     * @codeCoverageIgnore
     */
    protected function _getContentInstance()
    {
        return $this;
    }

    /**
     * Sets options at the construction of a new instance
     * @param  mixed                          $options Initial options for the content:
     *                                                 - label       the label of the content
     * @return \BackBee\ClassContent\AContent
     */
    protected function _setOptions($options = null)
    {
        if (null !== $options) {
            $options = (array) $options;

            if (true === array_key_exists('label', $options)) {
                $this->setLabel($options['label']);
            }
        }

        return $this;
    }

    /**
     * Returns the type of a given value, either classname, array or scalar
     * @param  mixed  $value
     * @return string
     */
    protected function _getType($value)
    {
        if (true === is_object($value)) {
            return ClassUtils::getRealClass($value);
        }

        if (true === is_array($value)) {
            return 'array';
        }

        return 'scalar';
    }

    /**
     * Checks for an accepted type
     * @param  mixed   $value the value from which the type will be checked
     * @param  string  $var   the element to be checks
     * @return Boolean
     */
    protected function _isAccepted($value, $var = null)
    {
        if ($this->_getContentInstance() instanceof ContentSet) {
            if (false === ($value instanceof AClassContent)) {
                return false;
            }

            $accept_array = $this->_accept;
        } else {
            if (null === $var) {
                return false;
            }

            if (false === array_key_exists($var, $this->_accept)) {
                return true;
            }

            $accept_array = $this->_accept[$var];
        }

        if (0 === count($accept_array)) {
            return true;
        }

        return in_array($this->_getType($value), $accept_array);
    }

    /**
     * Adds a subcontent to the colection.
     * @param  \BackBee\ClassContent\AClassContent $value
     * @return string                              the unique identifier of the add subcontent
     * @codeCoverageIgnore
     */
    protected function _addSubcontent(AClassContent $value)
    {
        return $value->getUid();
    }

    /**
     * Removes the association with subcontents of the element $var
     * @param string $var
     * @codeCoverageIgnore
     */
    protected function _removeSubcontent($var)
    {
        // To be overload by \BackBee\ClassContent\AClassContent
    }

    /**
     * Return the serialized string of an array
     * @param  array  $var
     * @return string
     */
    protected function _arrayToStdClass($var)
    {
        $result = new \stdClass();

        if (true === is_array($var)) {
            foreach ($var as $key => $value) {
                $result->$key = $value;
            }
        }

        return $result;
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*               Implementation of IObjectIdentifiable                    */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Returns a unique identifier for this domain object.
     * @return string
     * @see \BackBee\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function getObjectIdentifier()
    {
        return $this->getType().'('.$this->getIdentifier().')';
    }

    /**
     * Returns the unique identifier for this object.
     * @return string
     * @see \BackBee\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function getIdentifier()
    {
        return $this->getUid();
    }

    /**
     * Returns the PHP class name of the object.
     * @return string
     * @see \BackBee\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function getType()
    {
        return ClassUtils::getRealClass($this);
    }

    /**
     * Checks for an explicit objects equality.
     * @param  \BackBee\Security\Acl\Domain\IObjectIdentifiable $identity
     * @return Boolean
     * @see \BackBee\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function equals(IObjectIdentifiable $identity)
    {
        return ($this->getType() === $identity->getType() && $this->getIdentifier() === $identity->getIdentifier());
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                   Implementation of IRenderable                        */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Returns the set of data
     * @param  string                                                   $var        The element to be return, if NULL, all datas are returned
     * @param  Boolean                                                  $forceArray Force the return as array
     * @return mixed                                                    Could be either one or array of scalar, array, AClassContent instance
     * @throws \BackBee\ClassContent\Exception\UnknownPropertyException Occurs when $var does not match an element
     * @throws \BackBee\AutoLoader\Exception\ClassNotFoundException     Occurs if the class of a subcontent can not be loaded
     */
    public function getData($var = null, $forceArray = false)
    {
        if (null === $var) {
            $datas = array();
            foreach (array_keys($this->_data) as $key) {
                $datas[$key] = $this->getData($key);
            }

            return $datas;
        }

        if (false === array_key_exists($var, $this->_data)) {
            if ($this->_getContentInstance() instanceof ContentSet) {
                return;
            } else {
                throw new Exception\UnknownPropertyException(sprintf('Unknown property %s in %s.', $var, ClassUtils::getRealClass($this)));
            }
        }

        $data = array();
        foreach ($this->_data[$var] as $type => $value) {
            if (true === is_array($value)) {
                $keys = array_keys($value);
                $values = array_values($value);

                $type = end($keys);
                $value = end($values);
            }

            if (0 === strpos($type, 'BackBee\ClassContent')) {
                if (false === class_exists($type)) {
                    throw new \BackBee\AutoLoader\Exception\ClassNotFoundException(sprintf('Unknown class content %s.', $type));
                }

                if (false !== $subcontent = $this->_getContentByDataValue($type, $value)) {
                    $data[] = $subcontent;
                }
            } else {
                $data[] = $value;
            }
        }

        if (false === $forceArray) {
            switch (count($data)) {
                case 0:
                    $data = null;
                    break;
                case 1:
                    $data = array_pop($data);
                    break;
            }
        }

        return $data;
    }

    /**
     * Returns TRUE if $var is an declared element of this content
     * @param  string  $var
     * @return boolean
     */
    public function hasElement($var)
    {
        return array_key_exists($var, $this->_data);
    }

    /**
     * Returns the first element of one of the provided class is exists
     * @param  mixed              $classnames
     * @return AClassContent|NULL
     */
    public function getFirstElementOfType($classnames)
    {
        if (false === is_array($classnames)) {
            $classnames = array($classnames);
        }

        foreach (array_keys($this->_data) as $key) {
            $element = $this->getData($key);

            if (false === is_object($element)) {
                continue;
            }

            foreach ($classnames as $classname) {
                if (true === is_a($element, $classname)) {
                    return $element;
                }
            }
        }

        return;
    }

    /**
     * Returns defined parameters
     * Upgrade of getParam method - parameter $var can follow this pattern:
     *     rendermode:array:selected
     * this will return $this->_parameters['rendermode']['array']['selected'] is it exists
     * @param  string $var  The parameter to be return, if NULL, all parameters are returned
     * @param  string $type The casting type of the parameter
     * @return mixed  the parameter value or NULL if unfound
     */
    public function getParam($var = null, $type = null)
    {
        if (null === $var) {
            return $this->_parameters;
        }

        $pieces = explode(':', $var);
        $var = array_shift($pieces);
        if (false === array_key_exists($var, $this->_parameters)) {
            return;
        }

        if (null !== $type && true === is_string($type)) {
            array_unshift($pieces, $type);
        }

        return $this->_getRecursivelyParam($this->_parameters[$var], $pieces);
    }

    /**
     * Goes all over the $param and keep looping until $pieces is empty to return
     * the values user is looking for
     * @param  mixed $param
     * @param  array $pieces
     * @return mixed
     */
    private function _getRecursivelyParam($param, array $pieces)
    {
        if (0 === count($pieces)) {
            return $param;
        }

        $key = array_shift($pieces);
        if (false === isset($param[$key])) {
            return;
        }

        return $this->_getRecursivelyParam($param[$key], $pieces);
    }

    /**
     * Checks for state of the content before rendering it
     * @return Boolean Always FALSE by default
     * @codeCoverageIgnore
     */
    public function isRenderable()
    {
        return false;
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                   Implementation of Serializable                       */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Return the serialized string of the content
     * @return string
     */
    public function serialize()
    {
        $serialized = new \stdClass();
        $serialized->uid = $this->getUid();
        $serialized->type = ClassUtils::getRealClass($this);
        $serialized->label = $this->getLabel();
        $serialized->revision = $this->getRevision();
        $serialized->state = $this->getState();
        $serialized->created = $this->getCreated();
        $serialized->modified = $this->getModified();
        $serialized->param = $this->_arrayToStdClass($this->getParam());

        if ($this->_getContentInstance() instanceof ContentSet) {
            $serialized->accept = $this->getAccept();
            $serialized->maxentry = $this->getMaxEntry();
            $serialized->minentry = $this->getMinEntry();

            $tmp = array();
            foreach ($this->getData() as $value) {
                $tmp[] = ($value instanceof AClassContent) ? $value->getUid() : $value;
            }
            $serialized->data = $tmp;
        } else {
            $serialized->accept = $this->_arrayToStdClass($this->getAccept());
            $serialized->maxentry = $this->_arrayToStdClass($this->getMaxEntry());
            $serialized->minentry = $this->_arrayToStdClass($this->getMinEntry());

            $serialized->data = new \stdClass();
            foreach ($this->getData() as $key => $value) {
                if (true === is_array($value)) {
                    $tmp = array();
                    foreach ($value as $val) {
                        $tmp[] = ($val instanceof AClassContent) ? $val->getUid() : $val;
                    }
                    $serialized->data->$key = $tmp;
                } else {
                    $serialized->data->$key = ($value instanceof AClassContent) ? $value->getUid() : $value;
                }
            }
        }

        return json_encode($serialized);
    }

    /**
     * Initialized the instance from a serialized string
     * @param  string                                                   $serialized
     * @param  Boolean                                                  $strict     If TRUE, all missing or additionnal element will generate an error
     * @return \BackBee\ClassContent\AClassContent                      The current instance
     * @throws \BackBee\ClassContent\Exception\UnknownPropertyException Occurs, in strict mode, when a
     *                                                                             property does not match an element
     */
    public function unserialize($serialized, $strict = false)
    {
        if (false === is_object($serialized)) {
            $serialized = json_decode($serialized);
        }

        foreach (get_object_vars($serialized) as $property => $value) {
            $property = '_'.$property;

            if (true === in_array($property, array('_created', '_modified')) || null === $value) {
                continue;
            } elseif ("_param" === $property) {
                foreach ($value as $param => $paramvalue) {
                    $this->setParam($param, $paramvalue);
                }
            } elseif ("_data" === $property) {
                foreach ($value as $el => $val) {
                    $this->$el = $val;
                }
            } elseif ("_value" === $property) {
                $this->value = $value;
            } elseif (false === property_exists($this, $property) && true === $strict) {
                throw new Exception\UnknownPropertyException(sprintf('Unknown property `%s` in %s.', $property, ClassUtils::getRealClass($this->_getContentInstance())));
            }
        }

        return $this;
    }

    public function getTemplateName()
    {
        return str_replace(array("BackBee".NAMESPACE_SEPARATOR."ClassContent".NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR), array("", DIRECTORY_SEPARATOR), get_class($this));
    }

    /**
     *
     *
     * @param boolean $return_array define if we return an array or the json_encode of array, default setted at false
     *
     * @return string|array
     * @deprecated since version 1.0
     */
    public function toJson($return_array = false)
    {
        return false === $return_array ? json_encode($this->jsonSerialize()) : $this->jsonSerialize();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $datas = array(
            'uid'        => $this->_uid,
            'label'      => $this->_label,
            'type'       => str_replace(
                array(self::CLASSCONTENT_BASE_NAMESPACE, '\\'),
                array('', '/'),
                get_class($this)
            ),
            'state'      => $this->_state,
            'created'    => $this->_created->getTimestamp(),
            'modified'   => $this->_modified->getTimestamp(),
            'revision'   => $this->_revision,
            'parameters' => $this->getParam(),
            'accept'     => array_map(
                function ($classname) {
                    return str_replace(
                        array(self::CLASSCONTENT_BASE_NAMESPACE, '\\'),
                        array('', '/'),
                        $classname
                    );
                },
                $this->getAccept()
            ),
            'minentry'   => $this->getMinEntry(),
            'maxentry'   => $this->getMaxEntry(),
        );

        $datas['elements'] = array();
        foreach ($this->getData() as $name => $content) {
            if ($content instanceof AContent) {
                $datas['elements'][$name] = $content->jsonSerialize();
            } elseif (is_scalar($content)) {
                $datas['elements'][$name] = $content;
            }
        }

        return $datas;
    }
}
