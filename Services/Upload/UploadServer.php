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

namespace BackBuilder\Services\Upload;

use BackBuilder\Services\Upload\Exception\UploadException,
    BackBuilder\Services\Utils\Error;
use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request;
use BackBuilder\Services\Rpc\JsonRPCServer;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Services
 * @subpackage  Upload
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class UploadServer extends JsonRPCServer
{

    public function getResponse($request, $request_payload = array())
    {
        $response = new Response();
        $response->headers->set('content-type', 'application/json');
        try {
            $this->_validateRequest($request);

            $requestArray = explode('.', $request->request->get('method'));
            if (!isset($requestArray[1]))
                throw new UploadException("Service not specified");
            else {
                $nameClass = $requestArray[0];
                $namespaceClass = $this->_getClassname($nameClass);
                $method = $requestArray[1];

                $reflectionMethod = $this->_validateMethodService($namespaceClass, $method);

                $this->_registerAnnotations($reflectionMethod);
            }

            if (false === $this->isExposed())
                throw new UploadException("Method:" . $method . " not exposed");

            if (NULL !== $this->_application)
                $this->_application->info(sprintf('Handling Upload RPC request `%s::%s`.', $namespaceClass, $method));
            $object = new $namespaceClass();
            $object->initService($this->_application);
            $result = call_user_func(array($object, $method), $request);

            $content = array(
                'result' => $result,
                'error' => NULL,
            );
            $response->setContent(json_encode($content));
        } catch (ForbidenAccessException $e) {
            $response->setStatusCode(403);
            $content = array('result' => NULL, 'error' => new Error($e));
        } catch (\Exception $e) {
            $content = array('result' => NULL, 'error' => new Error($e));
        }

        return $response;
    }

    /**
     * 
     * @inherited
     */
    protected function _validateMethodService($classname, $method)
    {
        try {
            $reflectionClass = new \ReflectionClass($classname);
        } catch (\ReflectionException $e) {
            throw new RpcException(sprintf('UploadServer: unknown service `%s`', $classname));
        }

        if (!$reflectionClass->implementsInterface('BackBuilder\Services\Local\IServiceLocal')) {
            throw new RpcException(sprintf('UploadServer: `%s` is not an AbstractServiceLocal object', $classname));
        }

        try {
            $reflectionMethod = $reflectionClass->getMethod($method);
        } catch (\ReflectionException $e) {
            throw new RpcException(sprintf('UploadServer: unknown method `%s` for `%s` service', $method, $classname));
        }

        return $reflectionMethod;
    }

    public function handle(Request $request = NULL, $request_payload = NULL)
    {
        if (NULL === $request && NULL === $this->_application) {
            return;
        }
        if (NULL === $request) {
            $request = $this->_application->getRequest();
        }
        //utf-8
        foreach ($request->request->keys() as $key) {
            $request->request->set($key, utf8_encode($request->request->get($key)));
        }

        return $this->getResponse($request, $request_payload);
    }

}
