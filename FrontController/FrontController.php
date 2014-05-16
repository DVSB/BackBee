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

namespace BackBuilder\FrontController;

use BackBuilder\BBApplication,
    BackBuilder\FrontController\Exception\FrontControllerException,
    BackBuilder\NestedNode\Page,
    BackBuilder\Routing\RouteCollection,
    BackBuilder\Routing\RequestContext,
    BackBuilder\Routing\Matcher\UrlMatcher,
    BackBuilder\Util\File,
    BackBuilder\Services\Content\Category,
    BackBuilder\Util\MimeType;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\KernelEvents,
    Symfony\Component\HttpKernel\HttpKernelInterface,
    Symfony\Component\HttpKernel\Exception\HttpExceptionInterface,
    Symfony\Component\HttpKernel\Event\FilterResponseEvent,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent,
    Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * The BackBuilder front controller
 * It handles and dispatches HTTP requests received
 *
 * @category    BackBuilder
 * @package     BackBuilder\FrontController
 * @copyright   Lp system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class FrontController implements HttpKernelInterface {

    /**
     * Iternal services map for Backbuilder
     * @var array
     */
    private $_internalServicesMap = array(
        "generate_classcontent_cache" => "generateClassContentCache"
    );

    /**
     * Current BackBuilder application
     * @var \BackBuilder\BBApplication
     */
    protected $_application;

    /**
     * Current request handled
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $_request;

    /**
     * Current request context
     * @var \BackBuilder\Routing\RequestContext
     */
    protected $_requestContext;

    /**
     * Routes collection defined
     * @var \BackBuilder\Routing\RouteCollection
     */
    protected $_routeCollection;
    protected $_actions = array();

    /**
     * Class constructor
     *
     * @access public
     * @param \BackBuilder\BBApplication $application The current BBapplication
     */
    public function __construct(BBApplication $application = null) 
    {
        $this->_application = $application;

        //$this->_routeCollection = $application->getRouting();
    }

    /**
     * Dispatches GetResponseEvent
     *
     * @access private
     * @param  string $eventName The name of the event to dispatch
     * @param  integer $type    The type of the request
     *                          (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     * @param  boolean $stopWithResponse Send response if TRUE and response exists
     */
    private function _dispatch($eventName, $controller = null, $type = self::MASTER_REQUEST, $stopWithResponse = true) 
    {
        if (null === $this->_application) {
            return;
        }

        if (null !== $this->_application->getEventDispatcher()) {
            $event = new GetResponseEvent(null === $controller ? $this : $controller, $this->getRequest(), $type);
            $this->_application->getEventDispatcher()->dispatch($eventName, $event);

            if ($stopWithResponse && $event->hasResponse()) {
                $this->_send($event->getResponse());
            }
        }
    }

    /**
     * Dispatch FilterResponseEvent then send response
     *
     * @acces private
     * @param Response $response The repsonse to filter then send
     * @param  integer $type    The type of the request
     *                          (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     */
    private function _send(Response $response, $type = self::MASTER_REQUEST) 
    {
        if (null !== $this->_application && null !== $this->_application->getEventDispatcher()) {
            $event = new FilterResponseEvent($this, $this->getRequest(), $type, $response);
            $this->_application->getEventDispatcher()->dispatch('frontcontroller.response', $event);
            $this->_application->getEventDispatcher()->dispatch(KernelEvents::RESPONSE, $event);
        }

        $response->send();
        exit(0);
    }

    /**
     * Invokes associated action to the current request
     *
     * @access private
     * @param array $matches An array of parameters provided by the URL matcher
     * @param int $type request type
     * @throws FrontControllerException
     */
    private function _invokeAction($matches, $type = self::MASTER_REQUEST) 
    {
        if (false === array_key_exists('_action', $matches)) {
            return;
        }

        $args = array();
        foreach ($matches as $key => $value) {
            if ('_' != substr($key, 0, 1)) {
                $args[$key] = $value;
            }
        }

        $this->getRequest()->attributes = new \Symfony\Component\HttpFoundation\ParameterBag($args);
        
        $this->_dispatch('frontcontroller.request');

        // logout Event dispatch
        if (null !== $this->getRequest()->get('logout') && true == $this->getRequest()->get('logout') && true === $this->getApplication()->getSecurityContext()->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->_dispatch('frontcontroller.request.logout');
            //$this->defaultAction($this->getRequest()->getPathInfo(), $sendResponse);
        }

        $actionKey = $matches['_route'] . '_' . $matches['_action'];

        if (isset($this->_actions[$actionKey]) && is_callable($this->_actions[$actionKey])) {
            /* nothing to do */
        } elseif (array_key_exists($matches['_action'], $this->_actions) && is_callable($this->_actions[$matches['_action']])) {
            $actionKey = $matches['_action'];
        }

        if (null !== $actionKey) {
            $controller = $this->_actions[$actionKey];
            
            $eventName = str_replace('\\', '.', strtolower(get_class($controller[0])));
            if (0 === strpos($eventName, 'backbuilder.')) {
                $eventName = substr($eventName, 12);
            }

            if (0 === strpos($eventName, 'frontcontroller.')) {
                $eventName = substr($eventName, 16);
            }

            $eventName .= '.pre' . $matches['_action'];
            $this->_dispatch($eventName . '.pre' . $matches['_action']);

            // dispatch kernel.controller event
            $event = new FilterControllerEvent($this, $controller, $this->getRequest(), $type);
            $this->_application->getEventDispatcher()->dispatch(KernelEvents::CONTROLLER, $event);
            // a listener could have changed the controller
            $controller = $event->getController();

            // get controller action arguments
            $actionArguments = $this->getApplication()->getContainer()->get('controller_resolver')->getArguments(
                $this->getRequest(),
                $controller
            );
            
            $response = call_user_func_array($controller, $actionArguments);

            return $response;
        } else {
            throw new FrontControllerException(sprintf('Unknown action `%s`.', $matches['_action']), FrontControllerException::BAD_REQUEST);
        }
      
    }

    /**
     * Flush a file content to HTTP response
     *
     * @access private
     * @param string $filename The filename to flush
     * @throws FrontControllerException
     */
    private function _flushfile($filename) 
    {
        if (false === file_exists($filename) || false === is_readable($filename) || true === is_dir($filename)) {
            throw new FrontControllerException(sprintf('The file `%s` can not be found (referer: %s).', $this->_request->getHost() . '/' . $this->_request->getPathInfo(), $this->_request->server->get('HTTP_REFERER')), FrontControllerException::NOT_FOUND);
        }

        try {
            $filestats = stat($filename);

            $response = new Response();

            $response->headers->set('Content-Type', MimeType::getInstance()->guess($filename));
            $response->headers->set('Content-Length', $filestats['size']);

            $response->setCache(array(
                'etag' => basename($filename),
                'last_modified' => new \DateTime('@' . $filestats['mtime']),
                'public' => 'public'
            ));

            $response->setContent(file_get_contents($filename));
            $this->_send($response);
        } catch (\Exception $e) {
            throw new FrontControllerException(sprintf('File `%s`can not be flushed.', basename($filename)), FrontControllerException::INTERNAL_ERROR, $e);
        }
    }

    /**
     * Returns current Backbuilder application
     *
     * @access public
     * @return BBApplication
     */
    public function getApplication() 
    {
        return $this->_application;
    }

    /**
     * Returns the current request
     *
     * @access public
     * @return Request
     */
    public function getRequest() 
    {
        if (null === $this->_request)
            $this->_request = Request::createFromGlobals();

        return $this->_request;
    }

    /**
     * Returns the current request context
     *
     * @access protected
     * @return RequestContext
     */
    protected function getRequestContext() 
    {
        if (null === $this->_requestContext) {
            $this->_requestContext = new RequestContext();
            $this->_requestContext->fromRequest($this->getRequest());
        }

        return $this->_requestContext;
    }

    /**
     * Returns the routes collection defined
     *
     * @access public
     * @return RouteCollection
     */
    public function getRouteCollection() 
    {
        $container = $this->_application->getContainer();
        if (null === $container->get('routing')) {
            $container->set('routing', new RouteCollection($this->_application));
            $routeConfig = $this->_application->getConfig()->getRouteConfig();
            $this->registerRoutes($this, $routeConfig);
        }

        return $container->get('routing');
    }

    /**
     * Handles the request when none other action was found
     *
     * @access public
     * @param string $uri The URI to handle
     * @throws FrontControllerException
     */
    public function defaultAction($uri = null, $sendResponse = true) 
    {
        if (null === $this->_application) {
            throw new FrontControllerException('A valid BackBuilder application is required.', FrontControllerException::INTERNAL_ERROR);
        }

        if (false === $this->_application->getContainer()->has('site')) {
            throw new FrontControllerException('A BackBuilder\Site instance is required.', FrontControllerException::INTERNAL_ERROR);
        }

        $site = $this->_application->getContainer()->get('site');
        $uri = preg_replace('/(.*)\.[hx]?[t]?m[l]?$/i', '$1', $uri);
        $redirect_page = $this->_application->getRequest()->get('bb5-redirect') ? ('false' !== $this->_application->getRequest()->get('bb5-redirect')) : TRUE;

        if ('_root_' == $uri) {
            $page = $this->_application->getEntityManager()
                    ->getRepository('BackBuilder\NestedNode\Page')
                    ->getRoot($site);
        } else {
            $page = $this->_application->getEntityManager()
                    ->getRepository('BackBuilder\NestedNode\Page')
                    ->findOneBy(array('_site' => $site,
                '_url' => '/' . $uri,
                '_state' => Page::getUndeletedStates()));
        }

        if (null !== $page && !$page->isOnline()) {
            $page = (null === $this->_application->getBBUserToken()) ? null : $page;
        }

        if (null === $page) {
            throw new FrontControllerException(sprintf('The URL `%s` can not be found.', $this->_request->getHost() . '/' . $uri), FrontControllerException::NOT_FOUND);
        }

        if (null !== $redirect = $page->getRedirect()) {
            if ((null === $this->_application->getBBUserToken()) || ((null !== $this->_application->getBBUserToken()) && (TRUE === $redirect_page))) {
                $redirect = $this->_application->getRenderer()->getUri($redirect);

                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
                header('Status: 301 Moved Permanently', false, 301);
                header('Location: ' . $redirect);
                exit();
            }
        }

        try {
            $this->_application->info(sprintf('Handling URL request `%s`.', $uri));

            if (null !== $this->getRequest()->get('bb5-mode')) {
                $response = new Response($this->_application->getRenderer()->render($page, $this->getRequest()->get('bb5-mode')));
            } else {
                $response = new Response($this->_application->getRenderer()->render($page));
            }

            if ($sendResponse) {
                $this->_send($response);
            } else {
                return $response;
            }
        } catch (\Exception $e) {
            throw new FrontControllerException(sprintf('An error occured while rendering URL `%s`.', $this->_request->getHost() . '/' . $uri), FrontControllerException::INTERNAL_ERROR, $e);
        }
    }

    public function rssAction($uri = null) 
    {
    	if (NULL === $this->_application)
            throw new FrontControllerException('A valid BackBuilder application is required.', FrontControllerException::INTERNAL_ERROR);

        if (FALSE === $this->_application->getContainer()->has('site'))
            throw new FrontControllerException('A BackBuilder\Site instance is required.', FrontControllerException::INTERNAL_ERROR);

        $site = $this->_application->getContainer()->get('site');
        if (FALSE !== $ext = strrpos($uri, '.'))
            $uri = substr($uri, 0, $ext);

        if ('_root_' == $uri) {
            $page = $this->_application->getEntityManager()
                    ->getRepository('BackBuilder\NestedNode\Page')
                    ->getRoot($site);
        } else {
            $page = $this->_application->getEntityManager()
                    ->getRepository('BackBuilder\NestedNode\Page')
                    ->findOneBy(array('_site' => $site,
                '_url' => '/' . $uri,
                '_state' => Page::getUndeletedStates()));
        }

        try {
            $this->_application->info(sprintf('Handling URL request `rss%s`.', $uri));

            $response = new Response($this->_application->getRenderer()->render($page, 'rss', null, 'rss.phtml'));
            $response->headers->set('Content-Type', 'text/xml');
            $response->setClientTtl(15);
            $response->setTtl(15);

            $this->_send($response);
        } catch (\Exception $e) {
            $this->defaultAction('/rss/' . $uri);
        }
    }

    /**
     * Handles a media file request
     *
     * @access public
     * @param string $filename The media file to provide
     * @throws FrontControllerException
     */
    public function mediaAction($type, $filename = null, $includePath = array())
    {
        $this->_validateResourcesAction($filename);

        $includePath = array_merge($includePath, array($this->_application->getStorageDir(), $this->_application->getMediaDir()));
        if (null !== $this->_application->getBBUserToken()) {
            $includePath[] = $this->_application->getTemporaryDir();
        }

        $matches = array();
        if (preg_match('/([a-f0-9]{3})\/([a-f0-9]{29})\/(.*)\.([^\.]+)/', $filename, $matches)) {
            $filename = $matches[1] . '/' . $matches[2] . '.' . $matches[4];
        } elseif (preg_match('/([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})\/.*\.([^\.]+)/', $filename, $matches)) {
            $filename = $matches[1] . $matches[2] . $matches[3] . $matches[4] . $matches[5] . $matches[6] . $matches[7] . $matches[8] . '.' . $matches[9];
            File::resolveMediapath($filename, null, array('include_path' => $includePath));
        } else {
            File::resolveMediapath($filename, null, array('include_path' => $includePath));
        }

        File::resolveFilepath($filename, null, array('include_path' => $includePath));

        $this->_application->info(sprintf('Handling image URL `%s`.', $filename));
        $this->_flushfile($filename);
    }

    /**
     * Handles a themes resources files request
     *
     * @access public
     * @param string $filename The media file to provide
     * @throws FrontControllerException
     */
    public function themesResourcesAction($type, $filename = null) 
    {
        $this->staticResourcesAction($type, $filename);
    }

    /**
     * Handles a static files request
     *
     * @access public
     * @param string $filename The media file to provide
     * @throws FrontControllerException
     */
    public function staticResourcesAction($type, $filename = null) 
    {
        $this->_validateResourcesAction($filename);

        $keyword = constant('BackBuilder\Theme\Theme::' . strtoupper($type) . '_DIR');
        File::resolveMediapath($filename, null, array('include_path' => $this->_application->getTheme()->getIncludePath($keyword)));

        $this->_application->info(sprintf('Handling %s URL `%s`.', $type, $filename));
        $this->_flushfile($filename);
    }

    /**
     * Handle static file request for bundle
     */
    public function serveBundleRessourcesAction($bundleName, $type, $filename) 
    {
        $pathInfos = array(
            "css" => "Ressources/Templates/css",
            "js" => "Ressources/Templates/javascript",
            "less" => "Ressources/Templates/less",
            "img" => "Ressources/Templates/img"
        );
        $bundleInfos = explode("-", $bundleName);
        $bundleNameInfos = array_map(function($str) {
                    return ucfirst($str);
                }, $bundleInfos);

        $completeBundleName = join("", $bundleNameInfos);
        $bundle = $this->_application->getContainer()->get('bundle.' . $completeBundleName);
        if (!is_null($bundle)) {
            if (in_array($type, array_keys($pathInfos))) {
                $filePath = $bundle->getBaseDir() . DIRECTORY_SEPARATOR . $pathInfos[$type] . DIRECTORY_SEPARATOR . $filename;
                $this->_application->info(sprintf('Handling %s URL `%s`.', $type, $filename));
                $this->_flushfile($filePath);
            }
        }
    }

    /**
     * Handles a resource file request
     *
     * @access public
     * @param string $filename The resource file to provide
     * @throws FrontControllerException
     */
    public function resourcesAction($filename = null, $base_dir = null) 
    {
        $this->_validateResourcesAction($filename);

        if (null === $base_dir) {
            File::resolveFilepath($filename, null, array('include_path' => $this->_application->getResourceDir()));
        } else {
            File::resolveFilepath($filename, null, array('base_dir' => $base_dir));
        }

        $this->_application->info(sprintf('Handling resource URL `%s`.', $filename));
        $this->_flushfile($filename);
    }

    /**
     * Handles a resource file request
     *
     * @access public
     * @param string $filename The resource file to provide
     * @throws FrontControllerException
     */
    public function bundleResourcesAction($bundle, $filename = null) 
    {
        $this->_validateResourcesAction($filename);

        $bundle = explode('-', $bundle);
        array_walk($bundle, function(&$value){
            $value = ucfirst($value);
        });
        $bundle = implode('', $bundle);
        $dir = $this->_application->getBundle($bundle)->getResourcesDir();

        File::resolveFilepath($filename, null, array('include_path' => $dir . DIRECTORY_SEPARATOR . 'Templates'));

        $this->_application->info(sprintf('Handling resource URL `%s`.', $filename));
        $this->_flushfile($filename);
    }

    /**
     * Handles an RPC request
     *
     * @access public
     * @throws FrontControllerException
     */
    public function rpcAction() 
    {
        if (null === $this->_application)
            throw new FrontControllerException('A valid BackBuilder application is required.', FrontControllerException::INTERNAL_ERROR);

        try {
            $response = $this->_application->getRpcServer()->handle($this->getRequest());
        } catch (\Exception $e) {
            throw new FrontControllerException('An error occured while processing RPC request', FrontControllerException::INTERNAL_ERROR, $e);
        }

        $this->_send($response);
    }

    /**
     * Handles an upload by RPC request
     *
     * @access public
     * @throws FrontControllerException
     */
    public function uploadAction() 
    {
        if (null === $this->_application) {
            throw new FrontControllerException('A valid BackBuilder application is required.', FrontControllerException::INTERNAL_ERROR);
        }

        try {
            $response = $this->_application->getUploadServer()->handle($this->getRequest());
        } catch (\Exception $e) {
            throw new FrontControllerException('An error occured while processing RPC request', FrontControllerException::INTERNAL_ERROR, $e);
        }

        $this->_send($response);
    }

    /**
     * Handles a request
     *
     * @access public
     * @param  Request $request The request to handle
     * @param  integer $type    The type of the request
     *                          (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     * @param  Boolean $catch   Whether to catch exceptions or not
     * @throws FrontControllerException
     */
    public function handle(Request $request = null, $type = self::MASTER_REQUEST, $catch = true) 
    {
        // request
        $event = new GetResponseEvent($this, $this->getRequest(), $type);
        
        $this->_application->getEventDispatcher()->dispatch(KernelEvents::REQUEST, $event);
        
        try {
            $this->_request = $request;
            
            $urlMatcher = new UrlMatcher($this->getRouteCollection(), $this->getRequestContext());
            $matches = $urlMatcher->match($this->getRequest()->getPathInfo());
            
            if($matches) {
                return $this->_invokeAction($matches, $type);
            }

            throw new FrontControllerException(sprintf('Unable to handle URL `%s`.', $this->getRequest()->getHost() . '/' . $this->getRequest()->getPathInfo()), FrontControllerException::NOT_FOUND);
        } catch (\Exception $e) {
            
            if (false === $catch) {
                throw $e;
            }

            return $this->handleException($e, $this->getRequest(), $type);
        }
    }
    
    
    /**
     * Handles an exception by trying to convert it to a Response.
     *
     * @param \Exception $e       An \Exception instance
     * @param Request    $request A Request instance
     * @param integer    $type    The type of the request
     *
     * @return Response A Response instance
     *
     * @throws \Exception
     */
    private function handleException(\Exception $e, $request, $type)
    {
        $event = new GetResponseForExceptionEvent($this, $request, $type, $e);
        $this->_application->getEventDispatcher()->dispatch(KernelEvents::EXCEPTION, $event);

        // a listener might have replaced the exception
        $e = $event->getException();

        if (!$event->hasResponse()) {
            throw $e;
        }

        $response = $event->getResponse();

        if(!$response->isClientError() && !$response->isServerError() && !$response->isRedirect()) {
            // ensure that we actually have an error response
            if ($e instanceof HttpExceptionInterface) {
                // keep the HTTP status code and headers
                $response->setStatusCode($e->getStatusCode());
                $response->headers->add($e->getHeaders());
            } elseif ($e instanceof FrontControllerException) {
                // keep the HTTP status code
                $response->setStatusCode($e->getStatusCode());
            } else {
                $response->setStatusCode(500);
            }
        }

        return $response;
    }

    /**
     * [addAction description]
     * @param [type] $handler [description]
     * @param [type] $action  [description]
     * @param [type] $prefix  [description]
     */
    public function addAction($handler, $action, $prefix = null) 
    {
        if (null !== $prefix) {
            $action = $prefix . '_' . $action;
        }

        $this->_actions[$action] = $handler;
    }

    private function _validateResourcesAction($value) 
    {
        if (null === $value) {
            throw new FrontControllerException('A filename is required', FrontControllerException::BAD_REQUEST);
        }

        if (null === $this->_application) {
            throw new FrontControllerException('A valid BackBuilder application is required.', FrontControllerException::INTERNAL_ERROR);
        }

        return true;
    }

    /**
     * Internal services handler
     * @access public
     * services call must return a Symfony\Component\HttpFoundation\Response
     */
    public function handleInternalServicesAction($servicename = null) 
    {
        try {
            if (isset($servicename) && array_key_exists($servicename, $this->_internalServicesMap)) {
                $serviceCallback = $this->_internalServicesMap[$servicename];
                if (method_exists($this, $serviceCallback)) {
                    $success = call_user_func(array($this, $serviceCallback));
                    if (!$success) {
                        throw new \Exception("Service returns an error");
                    }

                    if ($success) {
                        $this->_send(new Response("Service returns successfully."));
                    }
                } else {
                    throw new \Exception(sprintf("<strong>method '%s' can't be found</strong>", $serviceCallback));
                }
            }
        } catch (\Exception $e) {
            $exception = ($e instanceof FrontControllerException ) ? $e : new FrontControllerException(sprintf('An error occured while processing URL `%s`.', $this->getRequest()->getPathInfo()), FrontControllerException::INTERNAL_ERROR, $e);
            $exception->setRequest($this->getRequest());
            throw $exception;
        }
    }

    /**
     * Allow the classContents list to be served faster by caching them
     * @access public
     */
    private function generateClassContentCache() 
    {
        $contents = array();
        $result = false;
        $categoryList = Category::getCategories($this->_application);
        if (is_array($categoryList) && count($categoryList)) {
            foreach ($categoryList as $cat) {
                $cat->setBBapp($this->_application);
                foreach ($cat->getContents() as $content) {
                    $contents[] = $content->__toStdObject(false);
                }
            }
            if (count($contents)) {
                /* save content here */
                $cache = $this->_application->getBootstrapCache();
                $cache->save(Category::getCacheKey(), json_encode($contents));
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Return the url to the provided route path
     * @param string $route_path
     * @return string
     */
    public function getUrlByRoutePath($route_path) 
    {
        if (null === $url = $this->getRouteCollection()->getRoutePath($route_path)) {
            $url = '/';
        }

        return $url;
    }

    /**
     * Register every valid route defined in $routeConfig array
     * 
     * @param  ABundle $defaultController used as default controller if a route comes without any specific controller
     * @param  array   $routeConfig       
     */
    public function registerRoutes($defaultController, array $routeConfig = null)
    {
        if (null === $routeConfig) {
            return;
        }
        
        $application = $this->getApplication();
        $router = $this->getRouteCollection();
        $router->pushRouteCollection($router, $routeConfig);
        $router->moveDefaultRoute($router);

        foreach ($routeConfig as $name => $route) {
            if (false === array_key_exists('defaults', $route) || false === array_key_exists('_action', $route['defaults'])) {
                $application->warning(sprintf('Unable to parse the action method for the route `%s`.', $name));
                continue;
            }

            $action = $route['defaults']['_action'];
            
            $controller = null;
            if (true === array_key_exists('_controller', $route['defaults'])) {
                $container = $application->getContainer();
                if (true === $container->has($route['defaults']['_controller'])) {
                    $controller = $container->get($route['defaults']['_controller']);
                } else {
                    $application->warning(sprintf(
                        'Unable to get a valid controller with id:`%s` for the route `%s`.', 
                        $route['defaults']['_controller'], $name
                    ));
                    continue;
                }
            } else {
                $controller = $defaultController;
            }
            
            $handlerKey = $action;

            $this->addAction(array($controller, $action), $handlerKey, $name);
        }
    }
}
