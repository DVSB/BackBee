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

namespace BackBee\ClassContent\Tests\Mock;

use BackBee\ClassContent\AClassContent;
use BackBee\ClassContent\ContentSet;
use BackBee\ClassContent\Element\text;
use BackBee\Tests\Mock\IMock;

/**
 * ClassContent mock emulate a yml content cached
 *
 * @Entity(repositoryClass="BackBee\ClassContent\Repository\ClassContentRepository")
 * @Table(name="content")
 * @category    BackBee
 * @package     BackBee\ClassContent\Tests\Mock
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class MockContent extends AClassContent implements IMock
{
    public function __construct($uid = null, $options = null)
    {
        parent::__construct($uid, $options);
        $this->_initData();
    }

    protected function _initData()
    {
        $this->_defineData(
            'title',
            '\BackBee\ClassContent\Element\text',
            array(
                'default' => array(
                    'value' => 'Title here',
                ),
                'label' => 'Title',
                'maxentry' => 1,
                'parameters' => array(
                    'aloha' =>  array(
                        'type' => 'scalar',
                        'options' => array(
                            'default' => 'lite',
                        ),
                    ),
                    'editable' => array(
                        'type' => 'boolean',
                        'options' => array(
                            'default' => 'true',
                        ),
                    ),
                ),
            )
        )->_defineData(
            'body',
            '\BackBee\ClassContent\ContentSet',
            array()
        )->_defineParam(
            'excludefromautobloc',
            'array',
            array(
                'default' => array(
                    'rendertype' => 'checkbox',
                    'label' => 'Exclude from autoblocs',
                    'default' => false,
                ),
            )
        );
        $this->_defineProperty(
            'name',
            'Mock Content'
        )->_defineProperty(
            'description',
            'Basic content yml'
        )->_defineProperty(
            'category',
            array(
                0 => 'Mocks',
            )
        )->_defineProperty(
            'indexation',
            array(
                0 => array(
                    0 => 'title->value',
                ),
            )
        );
        parent::_initData();
    }

    public function load()
    {
        $this->title = new text();
        $this->title->value = 'This is the title';
        $this->body = new ContentSet();
    }

    public function defineData($var, $type = 'scalar', $options = null, $updateAccept = true)
    {
        return parent::_defineData($var, $type, $options, $updateAccept);
    }

    public function defineProperty($var, $value)
    {
        return parent::_defineProperty($var, $value);
    }

    public function defineParam($var, $type = 'scalar', $options = null)
    {
        return parent::_defineParam($var, $type, $options);
    }

    public function isAccepted($value, $var = null)
    {
        return parent::_isAccepted($value, $var);
    }
}
