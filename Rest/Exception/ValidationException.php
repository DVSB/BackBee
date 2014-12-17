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

namespace BackBee\Rest\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationList;
/**
 * Body listener/encoder
 *
 * @category    BackBee
 * @package     BackBee\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class ValidationException extends BadRequestHttpException
{
    /**
     *
     * @var ConstraintViolationList
     */
    protected $violations;

    /**
     *
     * @param ConstraintViolationList $violations
     */
    public function __construct(ConstraintViolationList $violations)
    {
        parent::__construct("Supplied data is invalid", $this);

        $this->violations = $violations;
    }

    /**
     * @return array
     */
    public function getErrorsArray()
    {
        $errors = array();

        //Symfony\Component\Validator\ConstraintViolation;
        foreach ($this->violations as $violation) {
            if (0 < strpos($violation->getPropertyPath(), '[')) {
                //parse into proper php array
                $parsedPath = [];
                parse_str($violation->getPropertyPath().'[]='.urlencode($violation->getMessage()), $parsedPath);
                $errors = array_merge_recursive($errors, $parsedPath);
            } else {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }
        }

        return $errors;
    }
}
