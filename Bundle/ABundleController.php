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

namespace BackBee\Bundle;

use BackBee\BBApplication;
use BackBee\FrontController\FrontController;
use BackBee\FrontController\Exception\FrontControllerException;
use BackBee\Utils\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Abstract class for bundle controller in BackBee5 application
 *
 * @category    BackBee
 * @package     BackBee\Bundle
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
abstract class ABundleController extends FrontController implements HttpKernelInterface
{
    /* @var \BackBee\Config\Config */

    private $config;
    /* @var \BackBee\Bundle\ABundle */
    private $bundle;
    /* @var \BackBee\Renderer\ARenderer */
    private $renderer;

    /**
     * Class constructor
     *
     * @access public
     * @param BBApplication $application The current BBapplication
     */
    public function __construct(ABundle $bundle)
    {
        $this->application = $bundle->getApplication();
        $this->bundle = $bundle;
        $this->config = $bundle->getConfig();
        $this->renderer = $this->application->getRenderer();
    }

    /**
     * Returns the current request
     *
     * @access public
     * @return Request
     */
    public function getRequest()
    {
        if (null === $this->request) {
            $this->request = $this->getApplication()->getRequest();
        }

        return $this->request;
    }

    /**
     * Returns the current bundle config
     *
     * @return \BackBee\Config\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Return the bundle
     * @return \BackBee\Bundle\ABundle
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Returns the current renderer
     *
     * @return \BackBee\Renderer\ARenderer
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    protected function rendererOverloaded()
    {
        $this->renderer;
    }

    /**
     * Returns the template directory of the current bundle
     *
     * @return string
     */
    public function getTemplateDir()
    {
        return File::realpath($this->bundle->getResourcesDir().DIRECTORY_SEPARATOR.$this->config->getBundleConfig('templates_dir')).DIRECTORY_SEPARATOR;
    }

    /**
     * Returns the layout directory of the current bundle
     *
     * @return string
     */
    public function getLayoutDir()
    {
        return File::realpath($this->bundle->getResourcesDir().DIRECTORY_SEPARATOR.$this->config->getBundleConfig('layouts_dir')).DIRECTORY_SEPARATOR;
    }

    /**
     * Dispatch FilterResponseEvent then send response
     *
     * @acces protected
     * @param Response $response The repsonse to filter then send
     * @param integer  $type     The type of the request
     *                           (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     */
    protected function send(Response $response, $type = self::MASTER_REQUEST)
    {
        if (null !== $this->application && null !== $this->application->getEventDispatcher()) {
            $event = new FilterResponseEvent($this, $this->getRequest(), $type, $response);
            $class = explode('\\', get_class($this));
            $this->application->getEventDispatcher()->dispatch(strtolower(end($class)).'.response', $event);
            $this->application->getEventDispatcher()->dispatch('frontcontroller.response', $event);
            $this->application->getEventDispatcher()->dispatch(KernelEvents::RESPONSE, $event);
        }

        $response->send();
        exit(0);
    }

    /**
     * Handles a request
     *
     * @access public
     * @param  Request                  $request The request to handle
     * @param  integer                  $type    The type of the request
     *                                           (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     * @param  Boolean                  $catch   Whether to catch exceptions or not
     * @throws FrontControllerException
     */
    public function handle(Request $request = null, $type = self::MASTER_REQUEST, $catch = true)
    {
        try {
            $this->request = $request;
            $this->dispatch(strtolower(end(explode('\\', get_class($this)))).'.request');
            $this->dispatch('frontcontroller.request');

            $urlMatcher = new UrlMatcher($this->getRouteCollection(), $this->getRequestContext());
            if ($matches = $urlMatcher->match($this->getRequest()->getPathInfo())) {
                $this->invokeAction($matches);
            }

            throw new FrontControllerException(sprintf('Unable to handle URL `%s`.', $this->getRequest()->getPathInfo()), FrontControllerException::NOT_FOUND);
        } catch (\Exception $e) {
            $exception = ($e instanceof FrontControllerException) ? $e : new FrontControllerException(sprintf('An error occured while processing URL `%s`.', $this->getRequest()->getPathInfo()), FrontControllerException::INTERNAL_ERROR, $e);
            $exception->setRequest($this->getRequest());
            throw $exception;
        }
    }
}
