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

namespace BackBuilder\Cache\Validator;

use BackBuilder\Cache\Validator\ValidatorInterface;

use Symfony\Component\HttpFoundation\Request;

/**
 * This cache validator checks requirements on application instance:
 *   - application debug value must be false
 *   - AND application isClientSAPI() must return false
 *   - AND current user must be not BBUser
 *   - AND application must be started
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache
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
     * @see BackBuilder\Cache\Validator\ValidatorInterface::isValid
     */
    public function isValid($object = null)
    {
        return in_array($this->request->getMethod(), $this->supported_methods);
    }

    /**
     * @see BackBuilder\Cache\Validator\ValidatorInterface::getGroups
     */
    public function getGroups()
    {
        return $this->groups;
    }
}
