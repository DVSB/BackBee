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

namespace BackBuilder\Workflow;

use BackBuilder\Site\Layout,
    BackBuilder\Security\Acl\Domain\AObjectIdentifiable;

/**
 * A workflow state for NestedNode\Page
 *
 * A negative code state is applied before online main state
 * A positive code state is applied after online main state
 *
 * A state can be associated to a specific Site\Layout and/or Listener
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Workflow
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @Entity(repositoryClass="BackBuilder\Workflow\Repository\StateRepository")
 * @Table(name="workflow")
 */
class State extends AObjectIdentifiable implements \Serializable
{

    /**
     * The unique identifier of the state
     * @var string
     * @Id @Column(type="string", name="uid")
     */
    protected $_uid;

    /**
     * The code of the workflow state
     * @var int
     * @Column(type="integer", name="code")
     */
    protected $_code;

    /**
     * The label of the workflow state
     * @var string
     * @Column(type="string", name="label")
     */
    protected $_label;

    /**
     * The optional layout to be applied for state.
     * @var \BackBuilder\Site\Layout
     * @ManyToOne(targetEntity="BackBuilder\Site\Layout")
     * @JoinColumn(name="layout", referencedColumnName="uid")
     */
    protected $_layout;

    /**
     * The optional listener classname
     * @var string
     * @Column(type="string", name="listener")
     */
    protected $_listener;

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
     * Returns the code of the state
     * @return int
     * @codeCoverageIgnore
     */
    public function getCode()
    {
        return $this->_code;
    }

    public function __construct($uid = null, array $options = array())
    {
        $this->_uid = (null === $uid) ? md5(uniqid('', true)) : $uid;

        if (true === array_key_exists('code', $options)) {
            $this->setCode($options['code']);
        }

        if (true === array_key_exists('label', $options)) {
            $this->setLabel($options['label']);
        }
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
     * Returns the layout if defined, NULL otherwise
     * @return \BackBuilder\Site\Layout
     * @codeCoverageIgnore
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Returns the listener classname if defined, NULL otherwise
     * @return string
     * @codeCoverageIgnore
     */
    public function getListener()
    {
        return $this->_listener;
    }

    /**
     * Sets the code
     * @param int $code
     * @return \BackBuilder\Workflow\State
     * @throws \BackBuilder\Exception\InvalidArgumentException
     */
    public function setCode($code)
    {
        if (false === \BackBuilder\Util\Numeric::isInteger($code)) {
            throw new \BackBuilder\Exception\InvalidArgumentException('The code of a workflow state has to be an integer');
        }

        $this->_code = $code;
        return $this;
    }

    /**
     * Sets the label
     * @param type $label
     * @return \BackBuilder\Workflow\State
     * @codeCoverageIgnore
     */
    public function setLabel($label)
    {
        $this->_label = strval($label);
        return $this;
    }

    /**
     * Sets the layout associated to this state
     * @param \BackBuilder\Site\Layout $layout
     * @return \BackBuilder\Workflow\State
     */
    public function setLayout(Layout $layout = null)
    {
        $this->_layout = $layout;
        return $this;
    }

    /**
     * Sets the optional listener classname
     * @param string $listener
     * @return \BackBuilder\Workflow\State
     * @codeCoverageIgnore
     */
    public function setListener($listener = null)
    {
        $this->_listener = $listener;
        return $this;
    }

    /**
     * Returns an array representation of the workflow state
     * @return string
     */
    public function toArray()
    {
        return array(
            'uid' => $this->getUid(),
            'code' => $this->getCode(),
            'label' => $this->getLabel(),
            'layout' => (null !== $this->getLayout()) ? $this->getLayout()->getUid() : null,
            'listenre' => $this->getListener()
        );
    }

    /**
     * Returns a string representation of the workflow state
     * @return string
     */
    public function serialize()
    {
        $serialized = new \stdClass();
        foreach ($this->toArray() as $key => $value) {
            $serialized->$key = $value;
        }

        return json_encode($serialized);
    }

    /**
     * Constructs the state from a string or object
     * @param mixed $serialized The string representation of the object.
     * @return \BackBuilder\Workflow\State
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if the serialized data can not be decode or, 
     *                                                         with strict mode, if a property does not exists
     */
    public function unserialize($serialized, $strict = false)
    {
        if (false === is_object($serialized)) {
            if (null === $serialized = json_decode($serialized)) {
                throw new InvalidArgumentException('The serialized value can not be unserialized to node object.');
            }
        }

        foreach (get_object_vars($serialized) as $property => $value) {
            $property = '_' . $property;
            if (true === in_array($property, array('_layout'))) {
                $this->$property = new Layout($value);
            } else if (true === property_exists($this, $property)) {
                $this->$property = $value;
            } else if (true === $strict) {
                throw new InvalidArgumentException(sprintf('Unknown property `%s` in %s.', $property, ClassUtils::getRealClass($this)));
            }
        }

        return $this;
    }

}