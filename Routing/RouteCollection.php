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

    public function __construct(BBApplication $application = null)
    {
        if (true === method_exists('Symfony\Component\Routing\RouteCollection', '__construct')) {
            parent::__construct();
        }

        if (null !== $application) {
            $this->_application = $application;
        }
    }

    public function addBundleRouting(ABundle $bundle)
    {
        $router = $this->_application->getController()->getRouteCollection();

        if (null !== $routeConfig = $bundle->getConfig()->getRouteConfig()) {
            $this->pushRouteCollection($router, $routeConfig);
            $this->moveDefaultRoute($router);
        }
    }

    public function pushRouteCollection($router, $routeCollection)
    {
        foreach ($routeCollection as $name => $route) {
            if (false === array_key_exists('pattern', $route) || false === array_key_exists('defaults', $route)) {
                $this->_application->warning(sprintf('Unable to parse the route definition `%s`.', $name));
                continue;
            }

            $router->add(
                $name, new Route($route['pattern'],
                $route['defaults'],
                array_key_exists('requirements', $route) ? $route['requirements'] : array())
            );

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

        return null;
    }

    /**
     * Return complete url which match with routeName and routeParams; you can also customize
     * the base url; by default it use current site base url
     * @param  string      $routeName   
     * @param  array|null  $routeParams 
     * @param  string|null $baseUrl     
     * @return string              
     */
    public function getUrlByRouteName($routeName, array $routeParams = null, $baseUrl = null)
    {
        $uri = $this->getRoutePath($routeName);
        if (null !== $routeParams && true === is_array($routeParams)) {
            foreach ($routeParams as $key => $value) {
                $uri = str_replace('{' . $key . '}', $value, $uri);
            }
        }

        return null !== $baseUrl && true === is_string($baseUrl)
            ? $baseUrl . $uri
            : $this->getUri($uri);
    }

    /**
     * Return $pathinfo with base url of current page if pahtinfo does not contains 'http' string
     * @param  string|null $pathinfo   
     * @param  string|null $defaultExt 
     * @return string             
     */
    public function getUri($pathinfo = null, $defaultExt = null)
    {
        if (null !== $pathinfo && preg_match('/^([a-zA-Z1-9\/_]*)http[s]?:\/\//', $pathinfo, $matches)) {
            return substr($pathinfo, strlen($matches[1]));
        }

        if ('/' !== substr($pathinfo, 0, 1)) {
            $pathinfo = '/' . $pathinfo;
        }

        $application = $this->_application;
        if (true === $application->isStarted() && null !== $application->getRequest()) {
            $request = $application->getRequest();

            if (null === $pathinfo) {
                $pathinfo = $request->getBaseUrl();
            }

            if (basename($request->getBaseUrl()) == basename($request->server->get('SCRIPT_NAME'))) {
                return $request->getSchemeAndHttpHost() 
                    . substr($request->getBaseUrl(), 0, -1 * (1 + strlen(basename($request->getBaseUrl())))) 
                    . $pathinfo;
            } else {
                return $request->getUriForPath($pathinfo);
            }
        }

        if (false === strpos(basename($pathinfo), '.') && '/' != substr($pathinfo, -1)) {
            if (null === $defaultExt) {
                if (null !== $application) {
                    if (null !== $application->getContainer()->get('site')) {
                        $defaultExt = $application->getContainer()->get('site')->getDefaultExtension();
                    }
                }
            }

            $pathinfo .= $defaultExt;
        }

        return $pathinfo;
    }
}