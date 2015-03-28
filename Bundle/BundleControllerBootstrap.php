<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\Bundle;

use Symfony\Component\HttpFoundation\Response;

use BackBee\Bundle\Exception\RequestErrorException;
use BackBee\FrontController\Exception\FrontControllerException;

/**
 * @category    BackBee
 * @package     BackBee\Bundle
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class BundleControllerBootstrap
{
    /**
     * @var BackBee\Bundle\AbstractBundle
     */
    protected $_bundle;

    /**
     * @var BackBee\BBApplication
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
     * @param BackBee\Bundle\AbstractBundle $bundle
     */
    public function __construct(BundleInterface $bundle)
    {
        $this->_bundle = $bundle;
        $this->_application = $bundle->getApplication();
        $this->_em = $this->_application->getEntityManager();
    }

    /**
     * It will act as the main controller of current controller; it will dispatch
     * resquest to the right protected action method
     *
     * @param  string                                                     $method
     * @param  mixed                                                      $args
     * @throws BackBee\FrontController\Exception\FrontControllerException if called action is not available
     */
    public function __call($method, $args)
    {
        if (true === method_exists($this, $method)) {
            // set default values
            $content = '';
            $statusCode = 200;
            $headers = array(
                'Content-Type' => 'text/html',
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
     * @throws BackBee\Bundle\Exception\RequestErrorException if current user is not a bbuser
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
     * @param string  $content    the string to print on client browser screen
     * @param integer $statusCode the request status code (default: 200)
     * @param array   $headers    headers of the response (default: void array)
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
        $response->send();
        die;
    }
}
