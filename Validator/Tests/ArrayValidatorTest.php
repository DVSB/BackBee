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

namespace BackBee\Validator\Tests;

use BackBee\Validator\ArrayValidator;

/**
 * ArrayValidator's validator
 *
 * @category    BackBee
 * @package     BackBee\Validator\Tests
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class ArrayValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $array_validator;
    private $array;

    /**
     * @covers BackBee\Validator\ArrayValidator::validate
     */
    public function testValidate()
    {
        $form_config = array(
            'foo' => array(
                'validator' => array(
                    'NotEmpty' => array(
                        'error' => 'Foot must not be empty.',
                    ),
                ),
            ),
            'bar' => array(
                'validator' => array(
                    'Email' => array(
                        'error' => 'Foot must be an valid email.',
                    ),
                ),
            ),
            'foobar' => array(
                'validator' => array(
                    'int' => array(
                        'validator' => array(
                            'max' => array(
                                'parameters' => array(10),
                            ),
                        ),
                        'error' => 'Max value for foobar is 10.',
                    ),
                ),
            ),
        );

        $array = array('foo' => null, 'bar' => null, 'foobar' => null);
        $datas = array('foo' => 'foo', 'bar' => 'bar', 'foobar' => 5);
        $errors = array();
        $array = $this->array_validator->validate($array, $datas, $errors, $form_config);
        $this->assertTrue(array_key_exists('bar', $errors));
        $this->assertFalse(array_key_exists('foo', $errors));
        $this->assertFalse(array_key_exists('foobar', $errors));

        $array = array('foo' => null, 'bar' => null, 'foobar' => null);
        $datas = array('foo' => '', 'bar' => 'foo@bar.com', 'foobar' => 11);
        $errors = array();
        $array = $this->array_validator->validate($array, $datas, $errors, $form_config);
        $this->assertFalse(array_key_exists('bar', $errors));
        $this->assertTrue(array_key_exists('foo', $errors));
        $this->assertTrue(array_key_exists('foobar', $errors));
    }

    /**
     * @covers BackBee\Validator\ArrayValidator::getData
     */
    public function testGetData()
    {
        $this->assertEquals('toto', $this->array_validator->getData('foo__bar', $this->array));
        $this->assertEquals('titi', $this->array_validator->getData('bar__foo__toto', $this->array));
        $this->assertEquals('titi', $this->array_validator->getData('foo_bar', $this->array));
        $this->assertNull($this->array_validator->getData('foo__toto', $this->array));
    }

    /**
     * @covers BackBee\Validator\ArrayValidator::setData
     */
    public function testSetData()
    {
        $this->array_validator->setData('foo__bar', 'bar', $this->array);
        $this->assertEquals('bar', $this->array['foo']['bar']);
    }

    /**
     * @covers BackBee\Validator\ArrayValidator::setData
     * @expectedException InvalidArgumentException
     */
    public function testSetDataIfIndexNotExist()
    {
        $this->array_validator->setData('foobarfoo', 'toto', $this->array);
    }

    /**
     * Sets up the fixture
     */
    public function setUp()
    {
        $this->array_validator = new ArrayValidator();
        $this->array = array(
                            'foo' => array(
                                'bar' => 'toto',
                            ),
                            'bar' => array(
                                'foo' => array(
                                    'toto' => 'titi',
                                ),
                            ),
                            'foo_bar' => 'titi',
                        );
    }
}
