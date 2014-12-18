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

namespace BackBee\Routing\Matcher;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher as sfRequestMatcher;

/**
 * @category    BackBee
 * @package     BackBee\Routing
 * @subpackage  Matcher
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class RequestMatcher extends sfRequestMatcher
{
    /**
     * Headers attributes.
     *
     * @var array
     */
    private $_headers;

    public function __construct($path = null, $host = null, $methods = null, $ip = null, array $attributes = array(), array $headers = array())
    {
        parent::__construct($path, $host, $methods, $ip, $attributes);
        $this->_headers = $headers;
    }

    /**
     * Adds a check for header attribute.
     *
     * @param string $key    The header attribute name
     * @param string $regexp A Regexp
     */
    public function matchHeader($key, $regexp)
    {
        $this->_headers[$key] = $regexp;
    }

    /**
     * Adds checks for header attributes.
     *
     * @param array    the header attributes to check array(attribute1 => regexp1, ettribute2 => regexp2, ...)
     */
    public function matchHeaders($attributes)
    {
        $attributes = (array) $attributes;
        foreach ($attributes as $key => $regexp) {
            $this->matchHeader($key, $regexp);
        }
    }

    public function matches(Request $request)
    {
        foreach ($this->_headers as $key => $pattern) {
            if (!preg_match('#'.str_replace('#', '\\#', $pattern).'#', $request->headers->get($key))) {
                return false;
            }
        }

        return parent::matches($request);
    }
}
