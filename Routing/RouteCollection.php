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

namespace BackBee\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection as sfRouteCollection;
use BackBee\BBApplication;
use BackBee\DependencyInjection\ContainerInterface;
use BackBee\DependencyInjection\Dumper\DumpableServiceInterface;
use BackBee\DependencyInjection\Dumper\DumpableServiceProxyInterface;
use BackBee\Site\Site;

/**
 * A RouteCollection represents a set of Route instances.
 *
 * When adding a route at the end of the collection, an existing route
 * with the same name is removed first. So there can only be one route
 * with a given name.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class RouteCollection extends sfRouteCollection implements DumpableServiceInterface, DumpableServiceProxyInterface
{
    const DEFAULT_URL = 0;
    const IMAGE_URL = 1;
    const MEDIA_URL = 2;
    const RESOURCE_URL = 3;

    /**
     * The current BBApplication.
     *
     * @var \BackBee\BBApplication
     */
    private $application;

    /**
     * @var array
     */
    private $raw_routes;

    /**
     * @var string
     */
    private $uri_prefixes;

    /**
     * @var boolean
     */
    private $is_restored;

    /**
     * Class constructor.
     *
     * @param \BackBee\BBApplication $application
     */
    public function __construct(BBApplication $application = null)
    {
        $this->application = $application;
        $this->raw_routes = array();

        $uri_prefixes = array();
        $container = $application->getContainer();
        if (true === $container->hasParameter('bbapp.routing.image_uri_prefix')) {
            $this->uri_prefixes[self::IMAGE_URL] = $container->getParameter('bbapp.routing.image_uri_prefix');
        }

        if (true === $container->hasParameter('bbapp.routing.media_uri_prefix')) {
            $this->uri_prefixes[self::MEDIA_URL] = $container->getParameter('bbapp.routing.media_uri_prefix');
        }

        if (true === $container->hasParameter('bbapp.routing.resource_uri_prefix')) {
            $this->uri_prefixes[self::RESOURCE_URL] = $container->getParameter('bbapp.routing.resource_uri_prefix');
        }

        $this->is_restored = false;
    }

    /**
     * [pushRouteCollection description].
     *
     * @param array $routes [description]
     *
     * @return [type] [description]
     */
    public function pushRouteCollection(array $routes)
    {
        foreach ($routes as $name => $route) {
            if (false === array_key_exists('pattern', $route) || false === array_key_exists('defaults', $route)) {
                $this->application->warning(sprintf('Unable to parse the route definition `%s`.', $name));
                continue;
            }

            $this->addRoute($name, $route);
        }

        $this->moveDefaultRoute();
    }

    /**
     * Return the path associated to a route.
     *
     * @param string $id
     *
     * @return url|NULL
     */
    public function getRoutePath($id)
    {
        if (null !== $this->get($id)) {
            return $this->get($id)->getPath();
        }

        return;
    }

    /**
     * Return complete url which match with routeName and routeParams; you can also customize
     * the base url; by default it use current site base url.
     *
     * @param string      $route_name
     * @param array|null  $route_params
     * @param string|null $base_url
     * @param boolean     $add_ext
     *
     * @return string
     */
    public function getUrlByRouteName(
        $route_name,
        array $route_params = null,
        $base_url = null,
        $add_ext = true,
        Site $site = null,
        $build_query = false
    ) {
        $uri = $this->getRoutePath($route_name);
        $params_to_add = array();
        if (null !== $route_params && true === is_array($route_params)) {
            foreach ($route_params as $key => $value) {
                $uri = str_replace('{'.$key.'}', $value, $uri, $count);
                if (0 === $count) {
                    $params_to_add[$key] = $value;
                }
            }
        }

        $path = null !== $base_url && true === is_string($base_url)
            ? $base_url.$uri.(false === $add_ext ? '' : $this->getDefaultExtFromSite($site))
            : $this->getUri($uri, false === $add_ext ? '' : null, $site)
        ;

        if (false === empty($params_to_add) && true === $build_query) {
            $path = $path.'?'.http_build_query($params_to_add);
        }

        return $path;
    }

    /**
     * Returns $pathinfo with base url of current page
     * If $site is provided, the url will be pointing on the associate domain.
     *
     * @param string             $pathinfo
     * @param string             $defaultExt
     * @param \BackBee\Site\Site $site
     *
     * @return string
     */
    public function getUri($pathinfo = null, $defaultExt = null, Site $site = null, $url_type = null)
    {
        if (true === array_key_exists((int) $url_type, $this->uri_prefixes)) {
            $pathinfo = '/'.$this->uri_prefixes[(int) $url_type].'/'.$pathinfo;
        }

        // If scheme already provided, return pathinfo
        if (null !== $pathinfo && preg_match('/^([a-zA-Z1-9\/_]*)http[s]?:\/\//', $pathinfo, $matches)) {
            return substr($pathinfo, strlen($matches[1]));
        }

        if ('/' !== substr($pathinfo, 0, 1)) {
            $pathinfo = '/'.$pathinfo;
        }

        // If no BBApplication or no Request, return pathinfo
        $application = $this->application;
        if (null === $application || false === $application->isStarted() || null === $application->getRequest()) {
            return $pathinfo;
        }

        if (null === $pathinfo) {
            $pathinfo = $this->getUriFromBaseUrl();
        }

        $pathinfo = str_replace('//', '/', $pathinfo);
        if (null === $site) {
            $site = $this->application->getSite();
        }

        $pathinfo = $this->getUriForSite($application->getRequest(), $pathinfo, $site);

        // If need add default extension provided or set from $site
        if (false === strpos(basename($pathinfo), '.') && '/' !== substr($pathinfo, -1)) {
            if (null === $defaultExt) {
                $defaultExt = $this->getDefaultExtFromSite($site);
            }

            $pathinfo .= $defaultExt;
        }

        return $pathinfo;
    }

    /**
     * @see BackBee\DependencyInjection\Dumper\DumpableServiceInterface::getClassProxy
     */
    public function getClassProxy()
    {
        return;
    }

    /**
     * @see BackBee\DependencyInjection\Dumper\DumpableServiceInterface::dump
     */
    public function dump(array $options = array())
    {
        return array('routes' => $this->raw_routes);
    }

    /**
     * @see BackBee\DependencyInjection\Dumper\DumpableServiceProxyInterface::restore
     */
    public function restore(ContainerInterface $container, array $dump)
    {
        foreach ($dump['routes'] as $name => $route) {
            $this->addRoute($name, $route);
        }

        $this->moveDefaultRoute();

        $this->is_restored = true;
    }

    /**
     * @see BackBee\DependencyInjection\Dumper\DumpableServiceInterface::isRestored
     */
    public function isRestored()
    {
        return $this->is_restored;
    }

    /**
     * [addRoute description].
     *
     * @param [type] $name  [description]
     * @param array  $route [description]
     */
    private function addRoute($name, array $route)
    {
        $this->raw_routes[$name] = $route;

        $this->add($name, new Route(
            $route['pattern'],
            $route['defaults'],
            true === array_key_exists('requirements', $route)
                ? $route['requirements']
                : array()
        ));

        $this->application->debug(sprintf('Route `%s` with pattern `%s` defined.', $name, $route['pattern']));
    }

    private function moveDefaultRoute()
    {
        $default_route = $this->get('default');
        if (null !== $default_route) {
            $this->remove('default');
            $this->add('default', $default_route);
        }
    }

    /**
     * Returns uri from pathinfo according to current request BaseUrl().
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string                                    $pathinfo
     *
     * @return string
     */
    private function getUriFromBaseUrl()
    {
        $request = $this->application->getRequest();
        $pathinfo = $request->getBaseUrl();

        if (basename($request->getBaseUrl()) == basename($request->server->get('SCRIPT_NAME'))) {
            return $request->getSchemeAndHttpHost()
                    .substr($request->getBaseUrl(), 0, -1 * (1 + strlen(basename($request->getBaseUrl()))))
                    .$pathinfo;
        } else {
            return $request->getUriForPath($pathinfo);
        }
    }

    /**
     * Returns uri from pathinfo according to site to be reached.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string                                    $pathinfo
     * @param \BackBee\Site\Site                        $site
     *
     * @return string
     */
    private function getUriForSite(Request $request, $pathinfo, Site $site)
    {
        return $request->getScheme().'://'.$site->getServerName().$pathinfo;
    }

    /**
     * Returns the default extension for a site.
     *
     * @param \BackBee\Site\Site $site
     *
     * @return string|null
     */
    private function getDefaultExtFromSite(Site $site = null)
    {
        if (null === $site) {
            $site = $this->application->getSite();
        }

        $extension = null;
        if (null !== $site) {
            $extension = $site->getDefaultExtension();
        }

        return $extension;
    }
}
