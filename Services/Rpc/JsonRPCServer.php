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

namespace BackBee\Services\Rpc;

use BackBee\BBApplication;
use BackBee\Services\Rpc\Exception\RpcException;
use BackBee\Services\Utils\Error;
use BackBee\Event\Event;
use BackBee\Exception\BBException;
use BackBee\Exception\MissingApplicationException;
use BackBee\Security\Exception\ForbiddenAccessException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Doctrine\Common\Annotations\SimpleAnnotationReader;

/**
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Rpc
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class JsonRPCServer
{
    /**
     * The current BackBee application
     * @var \BackBee\BBApplication
     */
    protected $_application;

    /**
     * A container for services annotations
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $_annotations;

    /**
     * Class constructor
     * @param \BackBee\BBApplication $application
     */
    public function __construct(BBApplication $application = null)
    {
        $this->_application = $application;
        $this->_annotations = new ContainerBuilder();

        $this->handleFatalErrors();
    }

    /**
     * @param  \Symfony\Component\HttpFoundation\Request $request
     * @param  array                                     $request_payload
     * @return boolean
     */
    public static function isRPCInvokedMethodSecured(Request $request, $request_payload = null)
    {
        $self = new self();

        if (null === $request_payload) {
            $request_payload = json_decode(file_get_contents("php://input"), true);
        }

        $self->_validateRequest($request);
        $requestArray = $self->_validateRequestPayload($request_payload);

        $classname = $self->_getClassname($requestArray[0]);
        $method = $requestArray[1];
        $reflectionMethod = $self->_validateMethodService($classname, $method);
        $self->_registerAnnotations($reflectionMethod);

        return $self->isSecured();
    }

    /**
     *
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @param  array                                     $request_payload
     * @return Symfony\Component\HttpFoundation\Response
     * @throws RpcException
     */
    public function getResponse(Request $request, $request_payload)
    {
        $object = $this;
        $method = 'getResponse';

        $content = array(
            'jsonrpc' => '2.0',
            'id' => null,
            'result' => null,
            'error' => true,
        );

        $response = new Response();
        $response->headers->set('content-type', 'application/json');

        try {
            $this->_validateRequest($request);
            $requestArray = $this->_validateRequestPayload($request_payload);

            $nameClass = $requestArray[0];
            $namespaceClass = $this->_getClassname($nameClass);
            $method = $requestArray[1];

            $reflectionMethod = $this->_validateMethodService($namespaceClass, $method);

            $this->_registerAnnotations($reflectionMethod);

            if (false === $this->isExposed()) {
                throw new RpcException("Method:".$method." not exposed");
            }

            $object = new $namespaceClass();
            call_user_func_array(array($object, "initService"), array($this->_application));

            $dispatcher = null;
            if (null !== $this->_application) {
                $this->_application->info(sprintf('Handling RPC request `%s::%s`.', get_class($object), $method));
                $dispatcher = $this->_application->getEventDispatcher();
            }

            $params = (isset($request_payload['params']) ? $request_payload['params'] : array());

            $this->_checkSecuredAccess()
                    ->_checkRestrictedAccess();

            if (null !== $dispatcher) {
                $event = new Event($object, array('method' => $method, 'params' => $params));
                $dispatcher->dispatch($dispatcher->getEventNamePrefix($object).'precall', $event);
                $params = $event->getArgument('params', $params);
            }

            if ($this->isLogable()) {
                $this->logActivity($namespaceClass, $method, $params);
            }

            $result = call_user_func_array(array($object, $method), $params);

            if (null !== $dispatcher && true === isset($event)) {
                $event->setArgument('result', $result);
                $dispatcher->dispatch($dispatcher->getEventNamePrefix($object).'postcall', $event);
                $result = $event->getArgument('result', $result);
            }

            $content = array(
                'jsonrpc' => '2.0',
                'id' => $request_payload['id'],
                'result' => $result,
                'error' => null,
            );
            if ($this->_application->isDebugMode()) {
                $content['debug'] = $this->collectProfilerData($request);
            }
        } catch (ForbiddenAccessException $e) {
            if (null !== $this->_application) {
                $this->_application->warning(sprintf('Forbidden access while handling RPC request `%s::%s`.', get_class($object), $method));
                $dispatcher = $this->_application->getEventDispatcher();
            }

            $response->setStatusCode(403);
            $content = array('jsonrpc' => '2.0', 'id' => $request_payload['id'], 'result' => null, 'error' => new Error($e));
        } catch (\Exception $e) {
            $content = array('jsonrpc' => '2.0', 'id' => $request_payload['id'], 'result' => null, 'error' => new Error($e));
        }
        $response->setContent(json_encode($content));

        return $response;
    }

    public function rpcPhpShutDownFunction()
    {
        $error = error_get_last();
        /* Ne pas afficher les notices */
        $notAllowedErrors = array(E_STRICT, E_NOTICE, E_WARNING, E_STRICT);
        if (is_array($error) && count($error) && !in_array($error["type"], $notAllowedErrors)) {
            $errMsg = $error["message"]." - File : ".$error["file"]." - Line :".$error["line"];
            try {
                $error = new \stdClass();
                $error->message = $errMsg;
                $return = array('jsonrpc' => '2.0', 'result' => null, 'error' => $error);
                $response = new Response();
                $response->headers->set('content-type', 'application/json');
                $response->setContent(json_encode($return));
                exit();
            } catch (BBException $e) {
            }
        }
    }

    /**
     * Handles a RPC request
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  array                                      $request_payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request = null, $request_payload = null)
    {
        if (null === $this->_application) {
            return;
        }

        if (null === $request) {
            $request = $this->_application->getRequest();
        }

        if (null === $request_payload) {
            $request_payload = json_decode(file_get_contents("php://input"), true);
        }

        return $this->getResponse($request, $request_payload);
    }

    protected function collectProfilerData(Request $request)
    {
        $profiler = $this->_application->getContainer()->get('profiler');
        $profile = $profiler->collect($request, new Response(), null);
        $debug = new \stdClass();
        $debug->memory = number_format($profile->getCollector('memory')->getMemory() / 1024 / 1024).' MB';
        $debug->db = new \stdClass();
        $debug->db->time = number_format($profile->getCollector('db')->getTime() * 1000, 2).' ms';
        $debug->db->count = $profile->getCollector('db')->getQueryCount();
        $debug->db->queries = $profile->getCollector('db')->getQueries();
        $debug->logger = array();
        foreach ($profile->getCollector('logger')->getLogs() as $log) {
            if (array_key_exists('priority', $log) && $log['priority'] < 5) {
                $debug->logger[] = $log;
            }
        }

        return $debug;
    }

    /**
     * Handles fatal errors that occur while handling a RPC request
     * @codeCoverageIgnore
     */
    protected function handleFatalErrors()
    {
        register_shutdown_function(array($this, 'rpcPhpShutDownFunction'));
    }

    /**
     * @return boolean
     * @codeCoverageIgnore
     */
    protected function isExposed()
    {
        return $this->_annotations->has('exposed');
    }

    /**
     * @return boolean
     */
    protected function isSecured()
    {
        return $this->_annotations->has('exposed') ? $this->_annotations->get('exposed')->secured : true;
    }

    /**
     * @return boolean
     */
    protected function isLogable()
    {
        return $this->_annotations->has('log') ? true : false;
    }

    /**
     * @return boolean
     */
    protected function isRestrict()
    {
        return $this->_annotations->has('restrict') ? true : false;
    }

    /**
     * @param string $classname
     * @param string $method
     * @param array  $params
     */
    protected function logActivity($classname, $method, $params)
    {
        $entity = null;
        if (
                $this->_annotations->get('log')->entity !== null &&
                $this->_annotations->get('log')->param !== null &&
                array_key_exists($this->_annotations->get('log')->param, $params)
        ) {
            $entity = $this->_application
                    ->getEntityManager()
                    ->find($this->_annotations->get('log')->entity, $params[$this->_annotations->get('log')->param]);
        }

        $this->_application->getEntityManager()
                ->getRepository('BackBee\Logging\AdminLog')
                ->log($this->_application->getBBUserToken()->getUser(), $classname, $method, $entity);
    }

    /**
     *
     * @param  string $request_method
     * @return string
     * @codeCoverageIgnore
     */
    protected function _getClassname($request_method)
    {
        return "\\".str_replace("_", "\\", $request_method);
    }

    /**
     * @param \ReflectionMethod $method
     */
    protected function _registerAnnotations($method)
    {
        $reader = new SimpleAnnotationReader();
        $reader->addNamespace('BackBee\Services\Rpc\Annotation');
        $this->_annotations->set('exposed', $reader->getMethodAnnotation($method, new Annotation\Exposed()));
        $this->_annotations->set('log', $reader->getMethodAnnotation($method, new Annotation\Log()));
        $this->_annotations->set('restrict', $reader->getMethodAnnotation($method, new Annotation\Restrict()));
    }

    protected function _parsePayload($request_payload = null, &$object = null, &$method = null)
    {
        if (null === $request_payload) {
            $request_payload = json_decode(file_get_contents("php://input"), true);
        }

        $requestArray = $this->_validateRequestPayload($request_payload);

        $nameClass = $requestArray[0];
        $namespaceClass = $this->_getClassname($nameClass);
        $method = $requestArray[1];

        $reflectionMethod = $this->_validateMethodService($namespaceClass, $method);
    }

    /**
     * Validates the RPC request
     * @return \BackBee\Services\Rpc\JsonRPCServer
     * @throws \BackBee\Services\Rpc\Exception\InvalidRequestException Occurs if the RPC request is invalid
     * @throws \BackBee\Services\Rpc\Exception\InvalidMethodException  Occurs if the RPC request method is invalid
     */
    protected function _validateRequest(Request $request)
    {
        if (false === $request->isXmlHttpRequest()) {
            throw new Exception\InvalidRequestException('Invalid XMLHttpRequest request.');
        }

        if ('POST' !== $request->getMethod()) {
            throw new Exception\InvalidMethodException('Invalid method request, waiting `POST` getting `%s`.');
        }

        return $this;
    }

    /**
     *
     * @param  string            $classname
     * @param  string            $method
     * @return \ReflectionMethod
     * @throws RpcException
     */
    protected function _validateMethodService($classname, $method)
    {
        try {
            $reflectionClass = new \ReflectionClass($classname);
        } catch (\ReflectionException $e) {
            throw new RpcException(sprintf('JsonRpcServer: unknown service `%s`', $classname));
        }

        if (!$reflectionClass->implementsInterface('BackBee\Services\Local\IServiceLocal')) {
            throw new RpcException(sprintf('JsonRpcServer: `%s` is not an AbstractServiceLocal object', $classname));
        }

        try {
            $reflectionMethod = $reflectionClass->getMethod($method);
        } catch (\ReflectionException $e) {
            throw new RpcException(sprintf('JsonRpcServer: unknown method `%s` for `%s` service', $method, $classname));
        }

        return $reflectionMethod;
    }

    /**
     * @param  type         $request_payload
     * @return array
     * @throws RpcException
     */
    protected function _validateRequestPayload($request_payload)
    {
        if (false === is_array($request_payload)
                || false === array_key_exists('id', $request_payload)
                || false === array_key_exists('method', $request_payload)) {
            throw new Exception\MalformedPayloadException('Invalid RPC request');
        }

        $requestArray = explode('.', $request_payload['method']);
        if (false === isset($requestArray[1])) {
            throw new Exception\MalformedPayloadException("Service not specified");
        }

        return $requestArray;
    }

    /**
     * Checks for a valid BackBee5 user on the current Site is need
     * @return \BackBee\Services\Rpc\JsonRPCServer
     * @throws \BackBee\Exception\MissingApplicationException       Occurs none BackBee application is defined
     * @throws \BackBee\Security\Exception\ForbiddenAccessException Occurs if the user can not admin the current Site
     */
    protected function _checkSecuredAccess()
    {
        if (null === $this->_application) {
            throw new MissingApplicationException('None BackBee application defined');
        }

        if (true === $this->isSecured()) {
            $securityContext = $this->_application->getSecurityContext();

            if (false === $securityContext->isGranted('sudo')) {
                if (null !== $securityContext->getACLProvider()
                        && false === $securityContext->isGranted('VIEW', $this->_application->getsite())) {
                    throw new ForbiddenAccessException('Forbidden acces');
                }
            }
        }

        return $this;
    }

    /**
     * Checks for a valid role for user if need
     * @return \BackBee\Services\Rpc\JsonRPCServer
     * @throws \BackBee\Exception\MissingApplicationException       Occurs none BackBee application is defined
     * @throws \BackBee\Security\Exception\ForbiddenAccessException Occurs if the user has not the expected role
     */
    protected function _checkRestrictedAccess()
    {
        if (null === $this->_application) {
            throw new MissingApplicationException('None BackBee application defined');
        }

        if (true === $this->isRestrict()) {
            $securityContext = $this->_application->getSecurityContext();

            if (false === $securityContext->isGranted($this->_annotations->get('restrict')->roles)) {
                throw new ForbiddenAccessException('Invalid role');
            }
        }

        return $this;
    }
}
