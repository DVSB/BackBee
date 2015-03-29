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

namespace BackBee\ClassContent;

use Symfony\Component\Security\Core\Util\ClassUtils;
use BackBee\AutoLoader\Exception\ClassNotFoundException;
use BackBee\ClassContent\Exception\UnknownPropertyException;
use BackBee\Renderer\IRenderable;
use BackBee\Security\Acl\Domain\IObjectIdentifiable;

use Doctrine\ORM\Mapping as ORM;

/**
 * Abstract class for every content and its revisions in BackBee.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @ORM\MappedSuperclass
 */
abstract class AContent implements IObjectIdentifiable, IRenderable, \JsonSerializable
{
    /**
     * BackBee's class content classname must be prefixed by this.
     */
    const CLASSCONTENT_BASE_NAMESPACE = 'BackBee\ClassContent\\';

    /**
     * Supported formats by ::jsonSerialize.
     */
    const JSON_DEFAULT_FORMAT = 0;
    const JSON_DEFINITION_FORMAT = 1;
    const JSON_CONCISE_FORMAT = 2;
    const JSON_INFO_FORMAT = 3;

    /**
     * Unique identifier.
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", name="uid")
     */
    protected $_uid;

    /**
     * The label of this content.
     *
     * @var string
     * @ORM\Column(type="string", name="label", nullable=true)
     */
    protected $_label;

    /**
     * The acceptable class name for values.
     *
     * @var array
     * @ORM\Column(type="array", name="accept")
     */
    protected $_accept = array();

    /**
     * A map of content.
     *
     * @var mixed
     * @ORM\Column(type="array", name="data")
     */
    protected $_data = array();

    /**
     * The content's parameters.
     *
     * @var array
     * @ORM\Column(type="array", name="parameters")
     */
    protected $_parameters = array();

    /**
     * The maximal number of items for values.
     *
     * @var array
     * @ORM\Column(type="array", name="maxentry")
     */
    protected $_maxentry = array();

    /**
     * The minimal number of items for values.
     *
     * @var array
     * @ORM\Column(type="array", name="minentry")
     */
    protected $_minentry = array();

    /**
     * The creation datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created")
     */
    protected $_created;

    /**
     * The last modification datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="modified")
     */
    protected $_modified;

    /**
     * Revision number.
     *
     * @var int
     * @ORM\Column(type="integer", name="revision")
     */
    protected $_revision;

    /**
     * The current state.
     *
     * @var int
     * @ORM\Column(type="integer", name="state")
     */
    protected $_state;

    /**
     * Formats supported by ::jsonSerialize.
     *
     * @var array
     */
    public static $jsonFormats = [
        'default'    => self::JSON_DEFAULT_FORMAT,
        'definition' => self::JSON_DEFINITION_FORMAT,
        'concise'    => self::JSON_CONCISE_FORMAT,
        'info'       => self::JSON_INFO_FORMAT,
    ];

    /**
     * Returns complete namespace of classcontent with provided $type.
     *
     * @param string $type
     *
     * @return string classname associated to provided
     *
     * @throws
     */
    public static function getClassnameByContentType($type)
    {
        $className = self::CLASSCONTENT_BASE_NAMESPACE.str_replace('/', NAMESPACE_SEPARATOR, $type);

        try {
            $exists = class_exists($className);
        } catch (\Exception $e) {
            $exists = false;
        }

        if (!$exists) {
            throw new \InvalidArgumentException("`$type` is not a classcontent valid type.");
        }

        return $className;
    }

    /**
     * Class constructor.
     *
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

        $this->setOptions($options);
    }

    /**
     * Magical function to get value for given element.
     *
     * @param string $var The name of the element
     *
     * @return mixed The value
     *
     * @throws UnknownPropertyException Occurs when $var does not match an element
     */
    public function __get($var)
    {
        if ($this->getContentInstance() instanceof ContentSet) {
            throw new UnknownPropertyException(sprintf(
                'Unknown property %s in %s.',
                $var,
                ClassUtils::getRealClass($this->getContentInstance())
            ));
        }

        return $this->getData($var);
    }

    /**
     * Magical function to set value to given element.
     *
     * @param string $var   The name of the element
     * @param mixed  $value The value to set
     *
     * @return AClassContent The current instance content
     *
     * @throws UnknownPropertyException Occurs when $var does not match an element
     */
    public function __set($var, $value)
    {
        if ($this->getContentInstance() instanceof ContentSet || !isset($this->_data[$var])) {
            throw new UnknownPropertyException(sprintf(
                'Unknown property %s in %s.',
                $var,
                ClassUtils::getRealClass($this->getContentInstance())
            ));
        }

        $values = is_array($value) ? $value : array($value);

        $this->__unset($var);
        $val = array();

        foreach ($values as $value) {
            if (
                (
                    isset($this->_maxentry[$var])
                    && 0 < $this->_maxentry[$var]
                    && $this->_maxentry[$var] == count($val)
                )
                || (
                    isset($this->_minentry[$var])
                    && count($val) < $this->_minentry[$var]
                    && $this->_maxentry[$var] == count($val)
                )
            ) {
                break;
            }

            if ($this->isAccepted($value, $var)) {
                $type = $this->_getType($value);

                if (is_object($value) && $value instanceof AClassContent) {
                    $value = $this->_addSubcontent($value);
                }

                $val[] = array($type => $value);
            }
        }

        $this->_data[$var] = $val;
        $this->_modified = new \DateTime();

        return $this->getContentInstance();
    }

    /**
     * Magical function to check the setting of an element.
     *
     * @param string $var The name of the element
     *
     * @return boolean TRUE if an element is set for $var, FALSE otherwise
     *
     * @throws UnknownPropertyException Occurs when $var does not match an element
     */
    public function __isset($var)
    {
        if ($this->getContentInstance() instanceof ContentSet) {
            throw new UnknownPropertyException(sprintf(
                'Unknown property %s in %s.',
                $var,
                ClassUtils::getRealClass($this->getContentInstance())
            ));
        }

        return array_key_exists($var, $this->_data) && 0 < count($this->_data[$var]);
    }

    /**
     * Magical function to unset an element.
     *
     * @param string $var The name of the element to unset
     *
     * @throws UnknownPropertyException Occurs when $var does not match an element
     */
    public function __unset($var)
    {
        if ($this->getContentInstance() instanceof ContentSet) {
            throw new UnknownPropertyException(sprintf(
                'Unknown property %s in %s.',
                $var,
                ClassUtils::getRealClass($this->getContentInstance())
            ));
        }

        if ($this->__isset($var)) {
            $this->_removeSubcontent($var);
            $this->_data[$var] = array();
        }
    }

    /**
     * Magical function to get a string representation of the content.
     *
     * @return string
     */
    public function __toString()
    {
        if (false === $this->isElementContent()) {
            return sprintf('%s(%s)', ClassUtils::getRealClass($this->getContentInstance()), $this->getUid());
        }

        $string = '';
        foreach ($this->getData() as $val) {
            $string .= $val;
        }

        return $string;
    }

    /**
     * Returns the unique identifier.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Returns the label.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * Return the current accepted subcontents.
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getAccept()
    {
        return $this->_accept;
    }

    /**
     * Returns the raw datas array.
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getDataToObject()
    {
        return $this->_data;
    }

    /**
     * Gets the maxentry.
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getMaxEntry()
    {
        return $this->_maxentry;
    }

    /**
     * Gets the minentry.
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getMinEntry()
    {
        return $this->_minentry;
    }

    /**
     * Returns the creation date.
     *
     * @return DateTime
     * @codeCoverageIgnore
     */
    public function getCreated()
    {
        return $this->_created;
    }

    /**
     * Returns the last modified date.
     *
     * @return DateTime
     * @codeCoverageIgnore
     */
    public function getModified()
    {
        return $this->_modified;
    }

    /**
     * Returns the revision number.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getRevision()
    {
        return $this->_revision;
    }

    /**
     * Returns the state.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * Sets the label.
     *
     * @param string $label
     *
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setLabel($label)
    {
        $this->_label = $label;

        return $this->getContentInstance();
    }

    /**
     * Set the acceptable classname.
     *
     * @param array $accept
     *
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setAccept($accept)
    {
        $this->_accept = $accept;

        return $this->getContentInstance();
    }

    /**
     * Sets one parameter.
     *
     * @param string $key   the parameter name to set
     * @param mixed  $value the parameter value, if null is passed it will unset provided key parameter
     *
     * @return AContent The current instance
     */
    public function setParam($key, $value = null)
    {
        if (null === $value) {
            unset($this->_parameters[$key]);
        } else {
            $this->_parameters[$key] = ['value' => $value];
        }

        return $this->getContentInstance();
    }

    /**
     * Sets all parameters.
     *
     * @param array $params
     *
     * @return self
     */
    public function setAllParams(array $params)
    {
        foreach ($params as $key => $value) {
            $this->setParam($key, $value);
        }

        return $this;
    }

    /**
     * Sets the maximum number of items for elements.
     *
     * @param array $maxentry
     *
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setMaxEntry(array $maxentry)
    {
        $this->_maxentry = $maxentry;

        return $this->getContentInstance();
    }

    /**
     * Sets the minimum number of items for elements.
     *
     * @param array $minentry
     *
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setMinEntry(array $minentry = null)
    {
        $this->_minentry = $minentry;

        return $this->getContentInstance();
    }

    /**
     * Sets creation date.
     *
     * @param \DateTime $created Current date time by default
     *
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setCreated(\DateTime $created = null)
    {
        $this->_created = null === $created ? new \DateTime() : $created;

        return $this->getContentInstance();
    }

    /**
     * Sets the last modification date.
     *
     * @param DateTime $modified Current date time by default
     *
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setModified(\DateTime $modified = null)
    {
        $this->_modified = null === $modified ? new \DateTime() : $modified;

        return $this->getContentInstance();
    }

    /**
     * Sets the revision number.
     *
     * @param int $revision
     *
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setRevision($revision)
    {
        $this->_revision = $revision;

        return $this->getContentInstance();
    }

    /**
     * Sets the state.
     *
     * @param int $state
     *
     * @return \BackBee\ClassContent\AContent The current instance
     * @codeCoverageIgnore
     */
    public function setState($state)
    {
        $this->_state = $state;

        return $this->getContentInstance();
    }

    /**
     * Is this content is a primary content ?
     *
     * @return Boolean TRUE if the content is a primary content
     * @codeCoverageIgnore
     */
    public function isElementContent()
    {
        return false !== strpos(
            ClassUtils::getRealClass($this->getContentInstance()),
            self::CLASSCONTENT_BASE_NAMESPACE.'Element\\'
        );
    }

    /**
     * Checks if the element accept subcontent.
     *
     * @param string $var the element
     *
     * @return Boolean TRUE if a subcontents are accepted, FALSE otherwise
     */
    public function acceptSubcontent($var)
    {
        if (!array_key_exists($var, $this->_accept)) {
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
     * Initialized data on postLoad doctrine event.
     */
    public function postLoad()
    {
    }

    /**
     * Checks for an accepted type.
     *
     * @param mixed  $value the value from which the type will be checked
     * @param string $var   the element to be checks
     *
     * @return Boolean
     */
    public function isAccepted($value, $var = null)
    {
        if ($this->getContentInstance() instanceof ContentSet) {
            if (!($value instanceof AClassContent)) {
                return false;
            }

            $accept_array = $this->_accept;
        } else {
            if (null === $var) {
                return false;
            }

            if (!array_key_exists($var, $this->_accept)) {
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
     * Return a subcontent instance by its type and value, FALSE if not found.
     *
     * @param string $type  The classname of the subcontent
     * @param string $value The value of the subcontent (uid)
     *
     * @return \BackBee\ClassContent\AClassContent|FALSE
     */
    protected function getContentByDataValue($type, $value)
    {
        return new $type($value);
    }

    /**
     * Returns the content.
     *
     * @return \BackBee\ClassContent\AClassContent
     * @codeCoverageIgnore
     */
    protected function getContentInstance()
    {
        return $this;
    }

    /**
     * Sets options at the construction of a new instance.
     *
     * @param mixed $options Initial options for the content:
     *                       - label: the label of the content
     *
     * @return self
     */
    protected function setOptions($options = null)
    {
        if (null !== $options) {
            $options = (array) $options;

            if (isset($options['label'])) {
                $this->setLabel($options['label']);
            }
        }

        return $this;
    }

    /**
     * Returns the type of a given value, either classname, array or scalar.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function _getType($value)
    {
        if (is_object($value)) {
            return ClassUtils::getRealClass($value);
        }

        if (is_array($value)) {
            return 'array';
        }

        return 'scalar';
    }

    /**
     * Adds a subcontent to the colection.
     *
     * @param \BackBee\ClassContent\AClassContent $value
     *
     * @return string the unique identifier of the add subcontent
     * @codeCoverageIgnore
     */
    protected function _addSubcontent(AClassContent $value)
    {
        return $value->getUid();
    }

    /**
     * Removes the association with subcontents of the element $var.
     *
     * @param string $var
     * @codeCoverageIgnore
     */
    protected function _removeSubcontent($var)
    {
    }

    /**
     * Returns a unique identifier for this domain object.
     *
     * @return string
     *
     * @see \BackBee\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function getObjectIdentifier()
    {
        return $this->getType().'('.$this->getIdentifier().')';
    }

    /**
     * Returns the unique identifier for this object.
     *
     * @return string
     *
     * @see \BackBee\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function getIdentifier()
    {
        return $this->getUid();
    }

    /**
     * Returns the PHP class name of the object.
     *
     * @return string
     *
     * @see \BackBee\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function getType()
    {
        return ClassUtils::getRealClass($this);
    }

    /**
     * Checks for an explicit objects equality.
     *
     * @param \BackBee\Security\Acl\Domain\IObjectIdentifiable $identity
     *
     * @return Boolean
     *
     * @see \BackBee\Security\Acl\Domain\IObjectIdentifiable
     * @codeCoverageIgnore
     */
    public function equals(IObjectIdentifiable $identity)
    {
        return ($this->getType() === $identity->getType() && $this->getIdentifier() === $identity->getIdentifier());
    }

    /**
     * Returns a string that represents shorten namespace of current classname.
     *
     * Example: BackBee\ClassContent\Element\Text => Element/Text
     *
     * @return string
     */
    public function getContentType()
    {
        return str_replace([self::CLASSCONTENT_BASE_NAMESPACE, '\\'], ['', '/'], $this->getType());
    }

    /**
     * Returns the set of data.
     *
     * @param string  $var        The element to be return, if NULL, all datas are returned
     * @param boolean $forceArray Force the return as array
     *
     * @return mixed Could be either one or array of scalar, array, AClassContent instance
     *
     * @throws UnknownPropertyException Occurs when $var does not match an element
     * @throws ClassNotFoundException   Occurs if the class of a subcontent can not be loaded
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

        if (!array_key_exists($var, $this->_data)) {
            if ($this->getContentInstance() instanceof ContentSet) {
                return;
            } else {
                throw new UnknownPropertyException(sprintf(
                    'Unknown property %s in %s.',
                    $var,
                    ClassUtils::getRealClass($this)
                ));
            }
        }

        $data = array();
        foreach ($this->_data[$var] as $type => $value) {
            if (is_array($value)) {
                $keys = array_keys($value);
                $values = array_values($value);

                $type = end($keys);
                $value = end($values);
            }

            if (0 === strpos($type, self::CLASSCONTENT_BASE_NAMESPACE)) {
                if (!class_exists($type)) {
                    throw new ClassNotFoundException(sprintf('Unknown class content %s.', $type));
                }

                if (false !== $subcontent = $this->getContentByDataValue($type, $value)) {
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
     * Returns TRUE if $var is an declared element of this content.
     *
     * @param string $var
     *
     * @return boolean
     */
    public function hasElement($var)
    {
        return array_key_exists($var, $this->_data);
    }

    /**
     * Returns the first element of one of the provided class is exists.
     *
     * @param mixed $classnames
     *
     * @return AClassContent|NULL
     */
    public function getFirstElementOfType($classnames)
    {
        if (!is_array($classnames)) {
            $classnames = array($classnames);
        }

        foreach (array_keys($this->_data) as $key) {
            $element = $this->getData($key);

            if (!is_object($element)) {
                continue;
            }

            foreach ($classnames as $classname) {
                if (is_a($element, $classname)) {
                    return $element;
                }
            }
        }
    }

    /**
     * Returns parameters if requested key exist.
     *
     * @param string $key The parameter to be return, if NULL, all parameters are returned
     *
     * @return mixed the parameter value or NULL if unfound
     */
    public function getParam($key)
    {
        $value = null;
        if (isset($this->_parameters[$key])) {
            $value = $this->_parameters[$key];
        }

        return $value;
    }

    /**
     * Returns all parameters.
     *
     * @return array
     */
    public function getAllParams()
    {
        return $this->_parameters;
    }

    /**
     * Checks for state of the content before rendering it.
     *
     * @return Boolean Always FALSE by default
     * @codeCoverageIgnore
     */
    public function isRenderable()
    {
        return false;
    }

    /**
     * Returns formatted template name.
     *
     * @return string
     */
    public function getTemplateName()
    {
        return str_replace(
            ["BackBee".NAMESPACE_SEPARATOR."ClassContent".NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR],
            ["", DIRECTORY_SEPARATOR],
            get_class($this)
        );
    }

    /**
     * Computes an array that contains current content data; it can also lighten result according to
     * requested format.
     *
     * @param integer $format
     *
     * @return array
     */
    public function jsonSerialize($format = self::JSON_DEFAULT_FORMAT)
    {
        $data = [
            'uid'        => $this->_uid,
            'label'      => $this->_label,
            'type'       => $this->getContentType(),
            'state'      => $this->_state,
            'created'    => $this->_created->getTimestamp(),
            'modified'   => $this->_modified->getTimestamp(),
            'revision'   => $this->_revision,
            'parameters' => $this->getAllParams(),
            'accept'     => array_map(
                function ($classname) {
                    return str_replace([self::CLASSCONTENT_BASE_NAMESPACE, '\\'], ['', '/'], $classname);
                },
                $this->getAccept()
            ),
            'minentry'   => $this->getMinEntry(),
            'maxentry'   => $this->getMaxEntry(),
            'elements'   => $this->computeElementsToJson($this->getData()),
        ];

        if (0 === count($data['parameters'])) {
            $data['parameters'] = new \ArrayObject();
        }

        return $this->formatJsonData($data, $format);
    }

    /**
     * Computes elements key for ::jsonSerialize.
     *
     * @param array $elements
     *
     * @return array
     */
    private function computeElementsToJson(array $elements)
    {
        $result = [];
        foreach ($elements as $key => $element) {
            if ($element instanceof AContent) {
                $result[$key] = [
                    'uid'  => $element->getUid(),
                    'type' => $element->getContentType(),
                ];
            } elseif (is_scalar($element)) {
                $result[$key] = $element;
            } elseif (is_array($element)) {
                $result[$key] = $this->computeElementsToJson($element);
            }
        }

        return $result;
    }

    /**
     * This method will lighten provided data into requested format, if format is equal to 0 this method
     * won't transform anything.
     *
     * @param array   $data
     * @param integer $format
     *
     * @return array
     */
    private function formatJsonData(array $data, $format)
    {
        if (self::JSON_DEFINITION_FORMAT === $format || self::JSON_CONCISE_FORMAT === $format) {
            unset($data['state'], $data['created'], $data['modified'], $data['revision']);
        }

        if (self::JSON_INFO_FORMAT === $format || self::JSON_CONCISE_FORMAT === $format) {
            unset($data['accept'], $data['label'], $data['minentry'], $data['maxentry']);
        }

        if (self::JSON_DEFINITION_FORMAT === $format || self::JSON_INFO_FORMAT === $format) {
            unset($data['elements']);
        }

        if (self::JSON_DEFINITION_FORMAT === $format) {
            unset($data['uid']);
        } elseif (self::JSON_INFO_FORMAT === $format) {
            unset($data['parameters']);
        }

        return $data;
    }
}
