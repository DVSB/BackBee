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

namespace BackBuilder\Event;

use Symfony\Component\EventDispatcher\Event as sfEvent;

/**
 * A generic class of event in BB application
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Event extends sfEvent
{

    /**
     * The target entity of the event
     * @var mixed
     */
    protected $_target;

    /**
     * Optional arguments passed to the event
     * @var mixed
     */
    protected $_args;

    /**
     * Class constructor
     * @param mixed $target The target of the event
     * @param mixed $eventArgs The optional arguments passed to the event
     */
    public function __construct($target, $eventArgs = NULL)
    {
        $this->_target = $target;
        $this->_args = $eventArgs;
    }

    /**
     * Returns the target of the event, optionally checks the class of the target
     * @param type $classname The optional class name to checks
     * @return mixed
     * @throws \InvalidArgumentException Occures on invalid type of target 
     *                                   according to the waited class name
     */
    public function getTarget($classname = null)
    {
        if (null === $classname
                || true === self::isTargetInstanceOf($classname)) {
            return $this->_target;
        }

        $target_type = gettype($this->_target);
        if ('object' === $target_type) {
            $target_type = get_class($this->_target);
        }

        throw new \InvalidArgumentException(sprintf('Invalid target : waiting `%s`, `%s` provided.', $classname, $this->_target));
    }

    /**
     * Checks if the target is of this class or has this class as one of its parents
     * @param string $classname The class name
     * @return bool TRUE if the object is of this class or has this class as one of
     *              its parents, FALSE otherwise
     */
    public function isTargetInstanceOf($classname)
    {
        return is_object($this->_target) ? is_a($this->_target, $classname) : false;
    }

    /**
     * Get argument by key.
     * @param string $key Key.
     * @param mixed $default default value to return
     * @return mixed Contents of array key.
     */
    public function getArgument($key, $default = null)
    {
        if ($this->hasArgument($key)) {
            return $this->_args[$key];
        }

        return $default;
    }

    /**
     * Add argument to event.
     * @param string $key   Argument name.
     * @param mixed  $value Value.
     * @return GenericEvent
     */
    public function setArgument($key, $value)
    {
        $this->_args[$key] = $value;

        return $this;
    }

    /**
     * Has argument.
     * @param string $key Key of arguments array.
     * @return boolean
     */
    public function hasArgument($key)
    {
        return is_array($this->_args) && array_key_exists($key, $this->_args);
    }

    /**
     * Return the arguments passed to the event
     * @return mixed|NULL
     */
    public function getEventArgs()
    {
        return $this->_args;
    }

    /**
     * Returns the current BackBuilder application
     * @return \BackBuilder\BBApplication
     * @throws \BadMethodCallException Occures if the event dispatcher is not a
     *                                 BackBuilder dispatcher
     */
    public function getApplication()
    {
        if ($this->getDispatcher() instanceof Dispatcher) {
            return $this->getDispatcher()->getApplication();
        }

        throw new \BadMethodCallException('Invalid event dispatcher used');
    }

}