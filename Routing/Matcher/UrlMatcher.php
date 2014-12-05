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

namespace BackBuilder\Routing\Matcher;

use BackBuilder\Util\File;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Matcher\UrlMatcher as sfUrlMatcher;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Routing
 * @subpackage  Matcher
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class UrlMatcher extends sfUrlMatcher
{
    /**
     * Handles specific route requirements.
     *
     * @param string $pathinfo The path
     * @param string $name     The route name
     * @param string $route    The route
     *
     * @return array The first element represents the status, the second contains additional information
     */
    protected function handleRouteRequirements($pathinfo, $name, Route $route)
    {
        $pathinfo = File::normalizePath($pathinfo, '/', false);
        $status = parent::handleRouteRequirements($pathinfo, $name, $route);

        if (self::REQUIREMENT_MATCH == $status[0] && 0 < count($route->getRequirements('HTTP-'))) {
            if (NULL === $request = $this->getContext()->getRequest()) {
                return array(self::REQUIREMENT_MISMATCH, null);
            }

            $requestMatcher = new RequestMatcher(null, null, null, null, array(), $route->getRequirements('HTTP-'));
            $status = array($requestMatcher->matches($request) ? self::REQUIREMENT_MATCH : self::REQUIREMENT_MISMATCH, null);
        }

        return $status;
    }

    /**
     * Tries to match a URL with a set of routes.
     *
     * @param string $pathinfo The path info to be parsed (raw format, i.e. not urldecoded)
     *
     * @return array An array of parameters
     *
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    public function match($pathinfo)
    {
        $pathinfo = File::normalizePath($pathinfo, '/', false);

        return parent::match($pathinfo);
    }
}
