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

namespace BackBee\Cache\Validator;

use Symfony\Component\HttpFoundation\Request;

/**
 * This cache validator checks requirements on request method
 *
 * @category    BackBee
 * @package     BackBee\Cache
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class RequestMethodValidator implements ValidatorInterface
{
    /**
     * The request we want to validate
     *
     * @var Symfony\Component\HttpFoundation\Request
     */
    private $request;

    /**
     * List of supported request methods (verbs)
     *
     * @var array
     */
    private $supported_methods;

    /**
     * list of group name this validator belong to
     *
     * @var array
     */
    private $groups;

    /**
     * constructor
     *
     * @param Request $request           the request we want to validate the method
     * @param array   $supported_methods list of supported methods
     * @param array   $groups            list of groups this validator belongs to
     */
    public function __construct(Request $request, array $supported_methods, $groups = array())
    {
        $this->request = $request;
        $this->supported_methods = array_map('strtoupper', $supported_methods);
        $this->groups = array_merge(array('default'), (array) $groups);
    }

    /**
     * @see BackBee\Cache\Validator\ValidatorInterface::isValid
     */
    public function isValid($object = null)
    {
        return in_array($this->request->getMethod(), $this->supported_methods);
    }

    /**
     * @see BackBee\Cache\Validator\ValidatorInterface::getGroups
     */
    public function getGroups()
    {
        return $this->groups;
    }
}
