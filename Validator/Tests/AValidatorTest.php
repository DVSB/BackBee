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

namespace BackBuilder\Validator\Tests;

/**
 * Validator test
 *
 * @category    BackBuilder
 * @package     BackBuilder\Validator\Tests
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class AValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $validator_test;

    /**
     * @covers BackBuilder\Validator\AValidator::truncatePrefix
     */
    public function testTruncatePrefix()
    {
        $this->assertEquals(array('bar' => 'foo'), $this->validator_test->truncatePrefix(array('foo_bar' => 'foo'), 'foo_'));
        $this->assertEquals(array('foo_bar' => 'foo'), $this->validator_test->truncatePrefix(array('foo_bar' => 'foo')));
    }

    /**
     * @covers BackBuilder\Validator\AValidator::truncatePrefix
     */
    public function testDoGeneralValidator()
    {
        $errors = array();
        $validator = 'notEmpty';
        $this->validator_test->doGeneralValidator('', 'foo', $validator, array('error' => 'An error occured'), $errors);
        $this->assertTrue(array_key_exists('foo', $errors));

        $errors = array();
        $this->validator_test->doGeneralValidator('bar', 'foo', $validator, array('error' => 'An error occured'), $errors);
        $this->assertFalse(array_key_exists('foo', $errors));

        $config = array('validator' => array(
                            'max' => array(
                                'parameters' => array(10),
                            ),
                        ),
                        'error' => 'Max value for foobar is 10.',
        );
        $errors = array();
        $validator = 'int';
        $this->validator_test->doGeneralValidator(11, 'foo', $validator, $config, $errors);
        $this->assertTrue(array_key_exists('foo', $errors));

        $errors = array();
        $this->validator_test->doGeneralValidator(5, 'foo', $validator, $config, $errors);
        $this->assertFalse(array_key_exists('foo', $errors));
    }

    /**
     * Sets up the fixture
     */
    public function setUp()
    {
        $this->validator_test = $this->getMockForAbstractClass('BackBuilder\Validator\AValidator');
    }
}
