<?php
namespace BackBuilder\Bundle;

use BackBuilder\BBApplication,
    BackBuilder\Bundle\ABundle,
    BackBuilder\FrontController\FrontController,
    BackBuilder\FrontController\Exception\FrontControllerException;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\KernelEvents,
    Symfony\Component\HttpKernel\HttpKernelInterface,
    Symfony\Component\HttpKernel\Event\FilterResponseEvent;

abstract class ABundleController extends FrontController implements HttpKernelInterface
{
    /* @var \BackBuilder\Config\Config */
    private $_config;
    /* @var \BackBuilder\Bundle\ABundle */
    private $_bundle;
    /* @var \BackBuilder\Renderer\ARenderer */
    private $_renderer;


    /**
     * Class constructor
     *
     * @access public
     * @param BBApplication $application The current BBapplication
     */
    public function __construct(ABundle $bundle)
    {
        $this->_application = $bundle->getApplication();
        $this->_bundle = $bundle;
        $this->_config = $bundle->getConfig();
        $this->_renderer = $this->_application->getRenderer();
    }

    /**
     * Returns the current request
     *
     * @access public
     * @return Request
     */
    public function getRequest()
    {
        if (NULL === $this->_request) {
            $this->_request = $this->getApplication()->getRequest();
        }

        return $this->_request;
    }

    /**
     * Returns the current bundle config
     * @codeCoverageIgnore
     * @return \BackBuilder\Config\Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Return the bundle
     * @codeCoverageIgnore
     * @return \BackBuilder\Bundle\ABundle
     */
    public function getBundle()
    {
        return $this->_bundle;
    }
    
    /**
     * Returns the current renderer
     * @codeCoverageIgnore
     * @return \BackBuilder\Renderer\ARenderer
     */
    public function getRenderer()
    {
        return $this->_renderer;
    }

    /**
     * Returns the template directory of the current bundle
     *
     * @return string
     */
    public function getTemplateDir()
    {
        return realpath($this->_bundle->getResourcesDir().DIRECTORY_SEPARATOR.$this->_config->getBundleConfig('templates_dir')).DIRECTORY_SEPARATOR;
    }

    /**
     * Returns the layout directory of the current bundle
     *
     * @return string
     */
    public function getLayoutDir()
    {
        return realpath($this->_bundle->getResourcesDir().DIRECTORY_SEPARATOR.$this->_config->getBundleConfig('layouts_dir')).DIRECTORY_SEPARATOR;
    }


    /**
     * Dispatch FilterResponseEvent then send response
     *
     * @acces protected
     * @param Response $response The repsonse to filter then send
     * @param  integer $type    The type of the request
     *                          (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     */
    protected function _send(Response $response, $type = self::MASTER_REQUEST)
    {
        if (NULL !== $this->_application && NULL !== $this->_application->getEventDispatcher()) {
            $event = new FilterResponseEvent($this, $this->getRequest(), $type, $response);
            $class = explode('\\', get_class($this));
            $this->_application->getEventDispatcher()->dispatch(strtolower(end($class)).'.response', $event);
            $this->_application->getEventDispatcher()->dispatch('frontcontroller.response', $event);
            $this->_application->getEventDispatcher()->dispatch(KernelEvents::RESPONSE, $event);
        }

        $response->send();
        exit(0);
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
            $this->_dispatch(strtolower(end(explode('\\', get_class($this)))).'.request');
            $this->_dispatch('frontcontroller.request');

            $urlMatcher = new UrlMatcher($this->getRouteCollection(), $this->getRequestContext());
            if ($matches = $urlMatcher->match($this->getRequest()->getPathInfo())) {
                $this->_invokeAction($matches);
            }

            throw new FrontControllerException(sprintf('Unable to handle URL `%s`.', $this->getRequest()->getPathInfo()), FrontControllerException::NOT_FOUND);

        } catch (\Exception $e) {
            $exception = ($e instanceof FrontControllerException )  ? $e : new FrontControllerException(sprintf('An error occured while processing URL `%s`.', $this->getRequest()->getPathInfo()), FrontControllerException::INTERNAL_ERROR, $e);
            $exception->setRequest($this->getRequest());
            throw $exception;
        }
    }
}