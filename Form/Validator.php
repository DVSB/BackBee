<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Form;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @category    BackBee
 * @package     BackBee\Form
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Validator
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private $_validators;

    /**
     * @var \BackBee\Form\ExecutionContext
     */
    private $_context;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    private $_request;

    public function __construct(\Symfony\Component\HttpFoundation\Request $request)
    {
        $this->_request = $request;
        $this->_context = new ExecutionContext();
        $this->_validators = new ContainerBuilder();
    }

    /**
     * @return array
     */
    public function getViolations()
    {
        return $this->_context->getViolations();
    }

    public function addViolation($message, $key)
    {
        $this->_context->addCustomViolation($message, $key);
    }

    /**
     * @param  string                                  $key
     * @return \Symfony\Component\Validator\Constraint
     */
    public function getConstraint($key, $options)
    {
        $class = '\Symfony\Component\Validator\Constraints\\'.ucfirst($key);

        return new $class($options);
    }

    /**
     * @param  string                                           $key
     * @return \Symfony\Component\Validator\ConstraintValidator
     */
    public function getValidator($key)
    {
        if (!$this->_validators->has($key)) {
            $class = '\Symfony\Component\Validator\Constraints\\'.ucfirst($key).'Validator';
            $this->_validators->set($key, new $class());
            $this->_validators->get($key)->initialize($this->_context);
        }

        return $this->_validators->get($key);
    }

    /**
     *
     *
     * @param string $type    Validation type
     * @param string $content Content to validate
     * @param array  $options Constraint options
     * @param string $message Alternative message
     */
    public function validate($type, $name, $message = null, array $options = null)
    {
        $content = $this->_request->get($name);
        $validator = $this->getValidator($type);
        $constraint = $this->getConstraint($type, $options);
        $message = is_null($message) ? $validator->getMessageTemplate() : $message;
        $this->_context->setNext($name, $content, $message);
        $validator->validate($content, $constraint, $name);
    }

    /**
     * @return boolean
     */
    public function isValide()
    {
        if (count($this->getViolations()) == 0) {
            return true;
        } else {
            return false;
        }
    }
}
