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

use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\ContentSet;
use BackBee\ClassContent\Element\Text;
use BackBee\Tests\Mock\MockInterface;

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
class MockContent extends AbstractClassContent implements MockInterface
{
    public function __construct($uid = null, $options = null)
    {
        parent::__construct($uid, $options);
        $this->initData();
    }

    protected function initData()
    {
        $this->defineData(
            'title',
            '\BackBee\ClassContent\Element\Text',
            array(
                'default' => array(
                    'value' => 'Title here',
                ),
                'label' => 'Title',
                'maxentry' => 1,
                'parameters' => array(
                    'aloha' =>  'lite',
                    'editable' => true,
                ),
            )
        )->defineData(
            'body',
            '\BackBee\ClassContent\ContentSet',
            array()
        )->defineData(
            'permid',
            'scalar'
        );
        $this->defineParam(
            'excludefromautobloc',
            [
                'rendertype' => 'checkbox',
                'label'      => 'Exclude from autoblocs',
                'default'    => false,
                'value'      => null,
            ]
        );
        $this->defineProperty(
            'name',
            'Mock Content'
        )->defineProperty(
            'description',
            'Basic content yml'
        )->defineProperty(
            'category',
            array(
                0 => 'Mocks',
            )
        )->defineProperty(
            'indexation',
            array(
                0 => array(
                    0 => 'title->value',
                ),
            )
        );

        parent::initData();
    }

    public function load()
    {
        $this->title = new Text();
        $this->title->value = 'This is the title';
        $this->body = new ContentSet();
    }

    public function mockedDefineData($var, $type = 'scalar', $options = null, $updateAccept = true)
    {
        return parent::defineData($var, $type, $options, $updateAccept);
    }

    public function mockedDefineProperty($var, $value)
    {
        return parent::defineProperty($var, $value);
    }

    public function mockedDefineParam($var, $options = null)
    {
        return parent::defineParam($var, $options);
    }

    public function getImageName()
    {
        return 'foobar';
    }
}
