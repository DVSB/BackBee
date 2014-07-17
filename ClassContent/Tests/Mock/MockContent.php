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

namespace BackBuilder\ClassContent\Tests\Mock;

use BackBuilder\Tests\Mock\IMock;
use BackBuilder\ClassContent\AClassContent;
use BackBuilder\ClassContent\ContentSet;
use BackBuilder\ClassContent\Element\text;

/**
 * ClassContent mock emulate a yml content cached
 *
 * @Entity(repositoryClass="BackBuilder\ClassContent\Repository\ClassContentRepository")
 * @Table(name="content")
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent\Tests\Mock
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class MockContent extends AClassContent implements IMock
{
    public function __construct($uid = NULL, $options = NULL)
    {
        parent::__construct($uid, $options);
        $this->_initData();
    }

    protected function _initData()
    {
        $this->_defineData(
            'title',
            '\BackBuilder\ClassContent\Element\text',
            array (
                'default' => array (
                    'value' => 'Title here',
                ),
                'label' => 'Title',
                'maxentry' => 1,
                'parameters' => array (
                    'aloha' =>  array (
                        'type' => 'scalar',
                        'options' => array (
                            'default' => 'lite',
                        ),
                    ),
                    'editable' => array (
                        'type' => 'boolean',
                        'options' => array (
                            'default' => 'true',
                        ),
                    ),
                ),
            )
        )->_defineData(
            'body',
            '\BackBuilder\ClassContent\ContentSet',
            array ()
        )->_defineParam(
            'excludefromautobloc',
            'array',
            array (
                'default' => array (
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
            array (
                0 => 'Mocks',
            )
        )->_defineProperty(
            'indexation',
            array (
                0 => array (
                    0 => 'title->value',
                )
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
}
