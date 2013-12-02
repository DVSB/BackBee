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

namespace BackBuilder\Routing;

use BackBuilder\BBApplication,
    BackBuilder\Bundle\ABundle;
use Symfony\Component\Routing\RouteCollection as sfRouteCollection;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Routing
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class RouteCollection extends sfRouteCollection
{

    private $_application;

    public function __construct(BBApplication $application = NULL)
    {
        if (method_exists('Symfony\Component\Routing\RouteCollection', '__construct'))
            parent::__construct();

        if (NULL !== $application) {
            $this->_application = $application;

            if (NULL !== $routeConfig = $this->_application->getConfig()->getRouteConfig()) {
                $this->pushRouteCollection($this, $routeConfig);
            }
        }
    }

    public function addBundleRouting(ABundle $bundle)
    {
        $router = $this->_application->getController()->getRouteCollection();

        if (NULL !== $routeConfig = $bundle->getConfig()->getRouteConfig()) {
            $this->pushRouteCollection($router, $routeConfig);
            $this->moveDefaultRoute($router);
        }
    }

    public function pushRouteCollection($router, $routeCollection)
    {
        foreach ($routeCollection as $name => $route) {
            if (FALSE === array_key_exists('pattern', $route) || FALSE === array_key_exists('defaults', $route)) {
                $this->_application->warning(sprintf('Unable to parse the route definition `%s`.', $name));
                continue;
            }

            $router->add($name, new Route($route['pattern'],
                            $route['defaults'],
                            array_key_exists('requirements', $route) ? $route['requirements'] : array()));

            $this->_application->debug(sprintf('Route `%s` with pattern `%s` defined.', $name, $route['pattern']));
        }
    }

    public function moveDefaultRoute($router)
    {
        $default_route = $router->get('default');
        $router->remove('default');
        $router->add('default', $default_route);
    }

    /**
     * Return the path associated to a route
     * @param string $id
     * @return null|url
     */
    public function getRoutePath($id)
    {
        if (null !== $this->get($id)) {
            return $this->get($id)->getPath();
        }

        return NULL;
    }

}