<?php

namespace BackBuilder\Bundle;

use BackBuilder\Bundle\ABundle,
    BackBuilder\Bundle\Exception\RequestErrorException,
    BackBuilder\FrontController\Exception\FrontControllerException;

use Symfony\Component\HttpFoundation\Response;

/**
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class BundleControllerBootstrap
{
    /**
     * @var BackBuilder\Bundle\ABundle
     */
    protected $_bundle;

    /**
     * @var BackBuilder\BBApplication
     */
    protected $_application;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $_em;

    /**
     * BundleControllerBootstrap's constructor; initialize 3 protected attributes:
     *     - $_bundle
     *     - $_application
     *     - $_em
     *     
     * @param BackBuilder\Bundle\ABundle $bundle 
     */
    public function __construct(ABundle $bundle)
    {
        $this->_bundle = $bundle;
        $this->_application = $bundle->getApplication();
        $this->_em = $this->_application->getEntityManager();
    }

    /**
     * It will act as the main controller of current controller; it will dispatch
     * resquest to the right protected action method
     * 
     * @param  string $method 
     * @param  mixed  $args   
     * @throws BackBuilder\FrontController\Exception\FrontControllerException if called action is not available
     */
    public function __call($method, $args)
    {
        if (true === method_exists($this, $method)) {
            // set default values
            $content = '';
            $statusCode = 200;
            $headers = array(
                'Content-Type' => 'text/html'
            );

            try {
                $result = call_user_func_array(array($this, $method), $args);

                if (
                    true === is_array($result) 
                    && true === isset($result['headers']) 
                    && true === isset($result['content'])
                ) {
                    $headers = $result['headers'];
                    $content = $result['content'];
                } else {
                    $content = $result;
                }
            } catch (RequestErrorException $e) {
                $content = $e->getMessage();
                $statusCode = $e->getStatusCode();
            }

            $this->sendResponse($content, $statusCode, $headers);
        } else {
            throw new FrontControllerException(
                sprintf('Unable to handle URL `%s`', $this->_application->getRequest()->getPathInfo()), 
                FrontControllerException::BAD_REQUEST
            );
        }
    }

    /**
     * Throws exception and stop current action if current user is not a bbuser
     * 
     * @throws BackBuilder\Bundle\Exception\RequestErrorException if current user is not a bbuser
     */
    protected function throwExceptionIfNotBBUser()
    {
        if (null === $this->_application->getBBUserToken()) {
            throw new RequestErrorException(
                '[401]: Unauthorized action, you must be authenticated as BBUser!',
                401
            );
        }
    }

    /**
     * Generic way to send response back to client browser
     * 
     * @param  string  $content    the string to print on client browser screen
     * @param  integer $statusCode the request status code (default: 200)
     * @param  array   $headers    headers of the response (default: void array)
     */
    private function sendResponse($content, $statusCode = 200, array $headers = array())
    {
        $response = new Response();

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);            
        }

        $response->setStatusCode($statusCode);
        $response->setContent($content);

        // Finally
        $response->send(); die;
    }
}
