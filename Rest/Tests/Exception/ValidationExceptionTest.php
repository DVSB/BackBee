<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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

namespace BackBee\Rest\Tests\Encoder;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use BackBee\Rest\Exception\ValidationException;
use BackBee\Tests\TestCase;

/**
 * Test for ValidationException class.
 *
 * @category    BackBee
 *
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
