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

namespace BackBee\Rest\Tests\Encoder;

use BackBee\Tests\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolation;
use BackBee\Rest\Exception\ValidationException;

/**
 * Test for ValidationException class
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Rest\Exception\ValidationException
 */
class ValidationExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function test___construct()
    {
        $exception = new ValidationException(
            new ConstraintViolationList([
                new ConstraintViolation(
                    'Validation Error', 'Validation Error', [], 'root', 'property', 'valueInvalid'
                ),
            ])
        );

        $this->assertInstanceOf('BackBee\Rest\Exception\ValidationException', $exception);
    }

    /**
     * @covers ::getErrorsArray
     */
    public function test_getErrorsArray()
    {
        $exception = new ValidationException(
            new ConstraintViolationList([
                new ConstraintViolation(
                    'Validation Error', 'Validation Error', [], 'root', 'property', 'valueInvalid'
                ),
            ])
        );
        $this->assertEquals([
            'property' => [
                'Validation Error',
            ],
        ], $exception->getErrorsArray());

        $exception = new ValidationException(
            new ConstraintViolationList([
                new ConstraintViolation(
                    'Validation Error', 'Validation Error', [], 'root', 'nested[property]', 'valueInvalid'
                ),
            ])
        );
        $this->assertEquals([
            'nested' => [
                'property' => [
                    'Validation Error',
                ],
            ],
        ], $exception->getErrorsArray());
    }
}
