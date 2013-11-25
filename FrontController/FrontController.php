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
    Symfony\Component\HttpKernel\Event\FilterResponseEvent,
    Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * The BackBuilder front controller
 * It handles and dispatches HTTP requests received
 *
 * @category    BackBuilder
 * @package     BackBuilder\FrontController
 * @copyright   Lp system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class FrontController implements HttpKernelInterface
{

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
    private function _dispatch($eventName, $type = self::MASTER_REQUEST, $stopWithResponse = TRUE)
    {
        if (NULL === $this->_application)
            return;

        if (NULL !== $this->_application->getEventDispatcher()) {
            $event = new GetResponseEvent($this, $this->getRequest(), $type);
            $this->_application->getEventDispatcher()->dispatch($eventName, $event);

            if ($stopWithResponse && $event->hasResponse())
                $this->_send($event->getResponse());
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
        if (NULL !== $this->_application && NULL !== $this->_application->getEventDispatcher()) {
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
     * @throws FrontControllerException
     */
    private function _invokeAction($matches)
    {
        if (FALSE === array_key_exists('_action', $matches))
            return;
        $args = array();
        foreach ($matches as $key => $value)
            if ('_' != substr($key, 0, 1))
                $args[$key] = $value;

        if (FALSE === method_exists($this, $matches['_action'])) {
            if (array_key_exists($matches['_action'], $this->_actions) && is_callable($this->_actions[$matches['_action']])) {
                return call_user_func_array($this->_actions[$matches['_action']], $args);
            }

            throw new FrontControllerException(sprintf('Unknown action `%s`.', $matches['_action']), FrontControllerException::BAD_REQUEST);
        }

        try {
            $this->_dispatch('frontcontroller.pre' . strtolower($matches['_action']));
            $result = call_user_func_array(array($this, $matches['_action']), $args);
        } catch (FrontControllerException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new FrontControllerException(sprintf('Action `%s` has generated an error.', $matches['_action']), FrontControllerException::INTERNAL_ERROR, $e);
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
        if (FALSE === file_exists($filename) || FALSE === is_readable($filename) || true === is_dir($filename))
            throw new FrontControllerException(sprintf('The file `%s` can not be found (referer: %s).', $this->_request->getHost() . '/' . $this->_request->getPathInfo(), $this->_request->server->get('HTTP_REFERER')), FrontControllerException::NOT_FOUND);

        try {
            $filestats = stat($filename);

            $response = new Response();

            $response->headers->set('Content-Type', MimeType::getInstance()->guess($filename));
            $response->headers->set('Content-Length', $filestats['size']);

            $response->setCache(array('etag' => basename($filename),
                'last_modified' => new \DateTime('@' . $filestats['mtime']),
                'public' => 'public'));

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
        if (NULL === $this->_request)
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
        if (NULL === $this->_requestContext) {
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
        if (NULL === $this->_routeCollection) {
            $this->_routeCollection = new RouteCollection($this->_application);
        }

        return $this->_routeCollection;
    }

    /**
     * Handles the request when none other action was found
     *
     * @access public
     * @param string $uri The URI to handle
     * @throws FrontControllerException
     */
    public function defaultAction($uri = NULL, $sendResponse = true)
    {
        if (NULL === $this->_application)
            throw new FrontControllerException('A valid BackBuilder application is required.', FrontControllerException::INTERNAL_ERROR);

        if (FALSE === $this->_application->getContainer()->has('site'))
            throw new FrontControllerException('A BackBuilder\Site instance is required.', FrontControllerException::INTERNAL_ERROR);

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

        if (NULL !== $page && !$page->isOnline())
            $page = (NULL === $this->_application->getBBUserToken()) ? NULL : $page;

        if (NULL === $page)
            throw new FrontControllerException(sprintf('The URL `%s` can not be found.', $this->_request->getHost() . '/' . $uri), FrontControllerException::NOT_FOUND);

        if (NULL !== $redirect = $page->getRedirect()) {
            if ((NULL === $this->_application->getBBUserToken()) || ((NULL !== $this->_application->getBBUserToken()) && (TRUE === $redirect_page))) {
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

            // logout Event dispatch
            if (null !== $this->getRequest()->get('logout')
                    && TRUE == $this->getRequest()->get('logout')
                    && true === $this->getApplication()->getSecurityContext()->isGranted('IS_AUTHENTICATED_FULLY')) {
                $this->_dispatch('frontcontroller.request.logout');
                //$this->defaultAction($this->getRequest()->getPathInfo(), $sendResponse);
            }

            if ($sendResponse)
                $this->_send($response);
            else
                return $response;
        } catch (\Exception $e) {
            throw new FrontControllerException(sprintf('An error occured while rendering URL `%s`.', $this->_request->getHost() . '/' . $uri), FrontControllerException::INTERNAL_ERROR, $e);
        }
    }

    public function rssAction($uri = NULL)
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
    public function mediaAction($type, $filename = NULL)
    {
        $this->_validateResourcesAction($filename);

        $includePath = array($this->_application->getStorageDir(), $this->_application->getMediaDir());
        if (NULL !== $this->_application->getBBUserToken()) {
            $includePath[] = $this->_application->getTemporaryDir();
        }

        $matches = array();
        if (preg_match('/([a-f0-9]{3})\/([a-f0-9]{29})\/(.*)\.([^\.]+)/', $filename, $matches)) {
            $filename = $matches[1] . '/' . $matches[2] . '.' . $matches[4];
        } elseif (preg_match('/([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})([a-f0-9]{4})\/.*\.([^\.]+)/', $filename, $matches)) {
            $filename = $matches[1] . $matches[2] . $matches[3] . $matches[4] . $matches[5] . $matches[6] . $matches[7] . $matches[8] . '.' . $matches[9];
            File::resolveMediapath($filename, NULL, array('include_path' => $includePath));
        } else {
            File::resolveMediapath($filename, NULL, array('include_path' => $includePath));
        }

        File::resolveFilepath($filename, NULL, array('include_path' => $includePath));

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
    public function themesResourcesAction($type, $filename = NULL)
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
    public function staticResourcesAction($type, $filename = NULL)
    {
        $this->_validateResourcesAction($filename);

        $keyword = constant('BackBuilder\Theme\Theme::' . strtoupper($type) . '_DIR');
        File::resolveMediapath($filename, NULL, array('include_path' => $this->_application->getTheme()->getIncludePath($keyword)));

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
    public function resourcesAction($filename = NULL, $base_dir = null)
    {
        $this->_validateResourcesAction($filename);

        if (NULL === $base_dir)
            File::resolveFilepath($filename, NULL, array('include_path' => $this->_application->getResourceDir()));
        else
            File::resolveFilepath($filename, NULL, array('base_dir' => $base_dir));

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
        if (NULL === $this->_application)
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
        if (NULL === $this->_application)
            throw new FrontControllerException('A valid BackBuilder application is required.', FrontControllerException::INTERNAL_ERROR);

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
    public function handle(Request $request = NULL, $type = self::MASTER_REQUEST, $catch = true)
    {
        try {
            $this->_request = $request;
            $this->_dispatch('frontcontroller.request');
            $urlMatcher = new UrlMatcher($this->getRouteCollection(), $this->getRequestContext());
            if ($matches = $urlMatcher->match($this->getRequest()->getPathInfo())) {
                $this->_invokeAction($matches);
            }

            throw new FrontControllerException(sprintf('Unable to handle URL `%s`.', $this->getRequest()->getHost() . '/' . $this->getRequest()->getPathInfo()), FrontControllerException::NOT_FOUND);
        } catch (\Exception $e) {
            $exception = ($e instanceof FrontControllerException ) ? $e : new FrontControllerException(sprintf('An error occured while processing URL `%s`.', $this->getRequest()->getPathInfo()), FrontControllerException::INTERNAL_ERROR, $e);
            $exception->setRequest($this->getRequest());
            throw $exception;
        }
    }

    public function addAction($handler, $action)
    {
        $this->_actions[$action] = $handler;
    }

    private function _validateResourcesAction($value)
    {
        if (NULL === $value)
            throw new FrontControllerException('A filename is required', FrontControllerException::BAD_REQUEST);

        if (NULL === $this->_application)
            throw new FrontControllerException('A valid BackBuilder application is required.', FrontControllerException::INTERNAL_ERROR);

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
                    if (!$success)
                        throw new \Exception("Service returns an error");
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

}