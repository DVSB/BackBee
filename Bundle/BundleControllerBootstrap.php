<?php

namespace BackBuilder\Bundle;

use BackBuilder\Bundle\ABundle;

use Symfony\Component\HttpFoundation\Response;

class BundleControllerBootstrap
{
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
     * [__construct description]
     * @param BBApplication $application [description]
     */
    public function __construct(ABundle $bundle)
    {
        $this->_bundle = $bundle;
        $this->_application = $bundle->getApplication();
        $this->_em = $this->_application->getEntityManager();
    }

    /**
     * [__call description]
     * @param  [type] $method [description]
     * @param  [type] $args   [description]
     * @return [type]         [description]
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
                sprintf('Unable to handle URL `%s`', $this->request->getPathInfo()), 
                FrontControllerException::BAD_REQUEST
            );
        }
    }

    /**
     * [sendResponse description]
     * @param  [type]  $content    
     * @param  integer $statusCode 
     */
    private function sendResponse($content, $statusCode = 200, array $contentType = array())
    {
        $response = new Response();

        foreach ($contentType as $name => $value) {
            $response->headers->set($name, $value);            
        }

        $response->setStatusCode($statusCode);
        $response->setContent($content);

        // Finally
        $response->send(); die;
    }
}
