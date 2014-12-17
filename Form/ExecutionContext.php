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

/**
 * @category    BackBee
 * @package     BackBee\Form
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class ExecutionContext implements \Symfony\Component\Validator\ExecutionContextInterface
{
    /**
     * @var array
     */
    private $globalContext = array();

    /**
     * @var string
     */
    private $_key;

    /**
     * @var string
     */
    private $_value;

    /**
     * @var string
     */
    private $_message;

    /**
     * Creates a new execution context.
     */
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function addViolation($message, array $params = array(), $invalidValue = null, $pluralization = null, $code = null)
    {
        $report = new \stdClass();
        $report->message = $this->_message;
        $report->params = $params;
        $report->invalidKey = $this->_key;
        $report->invalidValue = $this->_value;
        $report->pluralization = $pluralization;
        $report->code = $code;

        $this->globalContext[$this->_key] = $report;
    }

    public function addCustomViolation($message, $key)
    {
        $report = new \stdClass();
        $report->message = $message;
        $report->invalidKey = $key;
        $this->globalContext[$key] = $report;
    }

    public function setNext($key, $value, $message)
    {
        $this->_key = $key;
        $this->_value = $value;
        $this->_message = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getViolations()
    {
        return $this->globalContext;
    }

    public function addViolationAt($subPath, $message, array $params = array(), $invalidValue = null, $pluralization = null, $code = null)
    {
    }

    public function getRoot()
    {
    }

    public function getPropertyPath($subPath = '')
    {
    }

    public function getClassName()
    {
    }

    public function getPropertyName()
    {
    }

    public function getValue()
    {
    }

    public function getGroup()
    {
    }

    public function getMetadata()
    {
    }

    public function validate($value, $subPath = '', $groups = null, $traverse = false, $deep = false)
    {
    }

    public function validateValue($value, $constraints, $subPath = '', $groups = null)
    {
    }

    public function getMetadataFactory()
    {
    }
}
