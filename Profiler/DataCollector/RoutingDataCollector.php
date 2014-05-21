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

namespace BackBuilder\Profiler\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\DependencyInjection\ContainerAwareInterface,
    Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Routing data collector
 *
 * @category    BackBuilder
 * @package     BackBuilder\Profiler
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class RoutingDataCollector extends DataCollector implements ContainerAwareInterface
{
    private $container;
    
    /**
     * 
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    /**
     * Collects the Information on the Route
     *
     * @param Request    $request   The Request Object
     * @param Response   $response  The Response Object
     * @param \Exception $exception The Exception
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $collection = $this->container->get('routing');
        $_ressources = $collection->getResources();
        $_routes = $collection->all();

        $routes = array();
        $ressources = array();

        foreach ($_ressources as $ressource) {
            $ressources[] = array(
                'type' => get_class($ressource),
                'path' => $ressource->__toString()
            );
        }

        foreach ($_routes as $routeName => $route) {
            $defaults = $route->getDefaults();
            $requirements = $route->getRequirements();

            $controller = isset($defaults['_controller']) ? $defaults['_controller'] : 'unknown';

            if($this->container->hasDefinition($controller)) {
                $controllerDefinition = $this->container->findDefinition($controller);
                /** @var \Symfony\Component\DependencyInjection\Definition $controllerDefinition **/
                $controller = '@' . $controller . ' - ' . $controllerDefinition->getClass();
            }
            
            
            $routes[$routeName] = array(
                'name' => $routeName,
                'pattern' => $route->getPattern(),
                'controller' => $controller,
                'method' => isset($requirements['_method']) ? $requirements['_method'] : 'ANY',
                'action' => isset($defaults['_action']) ? $defaults['_action'] : 'n/a',
            );
        }
        ksort($routes);
        $this->data['matchRoute'] = $request->attributes->get('_route');
        $this->data['routes'] = $routes;
        $this->data['ressources'] = $ressources;
    }

    /**
     * Returns the Amount of Routes
     *
     * @return integer Amount of Routes
     */
    public function getRouteCount()
    {
        return count($this->data['routes']);
    }

    /**
     * Returns the Matched Routes Information
     *
     * @return array Matched Routes Collection
     */
    public function getMatchRoute()
    {
        return $this->data['matchRoute'];
    }

    /**
     * Returns the Ressources Information
     *
     * @return array Ressources Information
     */
    public function getRessources()
    {
        return $this->data['ressources'];
    }

    /**
     * Returns the Amount of Ressources
     *
     * @return integer Amount of Ressources
     */
    public function getRessourceCount()
    {
        return count($this->data['ressources']);
    }

    /**
     * Returns all the Routes
     *
     * @return array Route Information
     */
    public function getRoutes()
    {
        return $this->data['routes'];
    }

    /**
     * Returns the Time
     *
     * @return int Time
     */
    public function getTime()
    {
        $time = 0;

        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'routing';
    }
}
