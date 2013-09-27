<?php
namespace BackBuilder\Routing;

use BackBuilder\BBApplication,
    BackBuilder\Bundle\ABundle;

use Symfony\Component\Routing\RouteCollection as sfRouteCollection;

class RouteCollection extends sfRouteCollection {
    private $_application;

    public function __construct(BBApplication $application = NULL) {
        if (method_exists('Symfony\Component\Routing\RouteCollection', '__construct'))
            parent::__construct ();
        
        if (NULL !== $application) {
            $this->_application = $application;

            if (NULL !== $routeConfig = $this->_application->getConfig()->getRouteConfig()) {
                $this->pushRouteCollection($this, $routeConfig);
            }
        }
    }
    
    public function addBundleRouting(ABundle $bundle) {
        $router = $this->_application->getController()->getRouteCollection();

        if (NULL !== $routeConfig = $bundle->getConfig()->getRouteConfig()) {
            $this->pushRouteCollection($router, $routeConfig);
            $this->moveDefaultRoute($router);
        }
    }

    public function pushRouteCollection($router, $routeCollection) {
        foreach($routeCollection as $name => $route) {
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

    public function moveDefaultRoute($router) {
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