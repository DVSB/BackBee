<?php

namespace BackBuilder\Services\Rpc;

use BackBuilder\BBApplication,
    BackBuilder\Services\Rpc\Exception\RpcException,
    BackBuilder\Services\Utils\Error,
    BackBuilder\Event\Event,
    BackBuilder\Exception\BBException,
    BackBuilder\Security\Exception\ForbidenAccessException,
    BackBuilder\Services\Rpc\Annotation;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\DependencyInjection\ContainerBuilder;

use Doctrine\Common\Annotations\SimpleAnnotationReader;

class JsonRPCServer
{
    /**
     * @var \BackBuilder\BBApplication
     */
    protected $_application;
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder 
     */
    protected $_annotations;

    /**
     * @param \BackBuilder\BBApplication $application
     */
    public function __construct(BBApplication $application = null)
    {
        $this->_application = (NULL !== $application) ? $application : null;
        $this->_annotations = new ContainerBuilder();
        $this->handleFatalErrors();
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array $request_payload
     * @return boolean
     */
    static public function isRPCInvokedMethodSecured(Request $request, $request_payload = NULL)
    {
        $self = new self();
       
        if (NULL === $request_payload) {
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
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param array $request_payload
     * @return Symfony\Component\HttpFoundation\Response
     * @throws RpcException
     */
    public function getResponse($request, $request_payload)
    {
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
            
            if (false === $this->isExposed())
                throw new RpcException("Method:" . $method . " not exposed");

            $object = new $namespaceClass();
            call_user_func_array(array($object, "initService"), array($this->_application));


            $dispatcher = null;
            if (NULL !== $this->_application) {
                $this->_application->info(sprintf('Handling RPC request `%s::%s`.', get_class($object), $method));
                $dispatcher = $this->_application->getEventDispatcher();
            }

            $params = (isset($request_payload['params']) ? $request_payload['params'] : array());

            if (NULL !== $dispatcher) {
                $event = new Event($object, array('method' => $method, 'params' => $params));
                $dispatcher->dispatch($dispatcher->getEventNamePrefix($object) . 'precall', $event);
                $params = $event->getArgument('params', $params);
            }
            
            if ($this->isRestrict()) {
                if (!$this->_application->getSecurityContext()->isGranted($this->_annotations->get('restrict')->roles)) {
                    $this->_application->warning(sprintf('User %s has try to acces at %s`.', $this->_application->getBBUserToken()->getUsername(), $method));
                    throw new ForbidenAccessException('Invalid role', ForbidenAccessException::UNAUTHORIZED_USER);
                }
            }

            if ($this->isLogable()) {
                $this->logActivity($namespaceClass, $method, $params);
            }

            $result = call_user_func_array(array($object, $method), $params);

            if (NULL !== $dispatcher && true === isset($event)) {
                $event->setArgument('result', $result);
                $dispatcher->dispatch($dispatcher->getEventNamePrefix($object) . 'postcall', $event);
                $result = $event->getArgument('result', $result);
            }

            $content = array(
                'jsonrpc' => '2.0',
                'id' => $request_payload['id'],
                'result' => $result,
                'error' => NULL,
            );
        } catch (ForbidenAccessException $e) {
            $response->setStatusCode(403);
            $content = array('jsonrpc' => '2.0', 'id' => $request_payload['id'], 'result' => NULL, 'error' => true);
        } catch (\Exception $e) {
            $content = array('jsonrpc' => '2.0', 'id' => $request_payload['id'], 'result' => NULL, 'error' => new Error($e));
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
            $errMsg = $error["message"] . " - File : " . $error["file"] . " - Line :" . $error["line"];
            try {
                $error = new \stdClass();
                $error->message = $errMsg;
                $return = array('jsonrpc' => '2.0', 'result' => NULL, 'error' => $error);
                $response = new Response();
                $response->headers->set('content-type', 'application/json');
                $response->setContent($return);
                exit();

            } catch (BBException $e) {
                
            }
        }
    }

    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array $request_payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request = NULL, $request_payload = NULL)
    {
        if (NULL === $request && NULL === $this->_application)
            return;

        if (NULL === $request)
            $request = $this->_application->getRequest();

        if (NULL === $request_payload)
            $request_payload = json_decode(file_get_contents("php://input"), true);

        return $this->getResponse($request, $request_payload);
    }

    protected function handleFatalErrors()
    {
        register_shutdown_function(array($this, 'rpcPhpShutDownFunction'));
    }

    /**
     * @return boolean
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
     * @param array $params
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
                           ->getRepository('BackBuilder\Logging\AdminLog')
                           ->log($this->_application->getBBUserToken()->getUser(), $classname, $method, $entity);
    }
    
    /**
     * 
     * @param string $request_method
     * @return string
     */
    protected function _getClassname($request_method)
    {
        return "\\" . str_replace("_", "\\", $request_method);
    }
    
    /**
     * @param \ReflectionMethod $method
     */
    protected function _registerAnnotations($method)
    {
        $reader = new SimpleAnnotationReader();
        $reader->addNamespace('BackBuilder\Services\Rpc\Annotation');
        $this->_annotations->set('exposed',  $reader->getMethodAnnotation($method, new Annotation\Exposed()));
        $this->_annotations->set('log',  $reader->getMethodAnnotation($method, new Annotation\Log()));
        $this->_annotations->set('restrict',  $reader->getMethodAnnotation($method, new Annotation\Restrict()));
    }
    
    /**
     * @param type $request
     * @throws UploadException
     */
    protected function _validateRequest($request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new UploadException("Invalid Http request");
        }
        if ('POST' !== $request->getMethod()) {
            throw new UploadException("Invalid post method");
        }
    }

    /**
     * 
     * @param string $classname
     * @param string $method
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

        if (!$reflectionClass->implementsInterface('BackBuilder\Services\Local\IServiceLocal')) {
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
    * @param type $request_payload
    * @return array
    * @throws RpcException
    */
    protected function _validateRequestPayload($request_payload)
    {
        if (empty($request_payload['id'])) {
            throw new RpcException("id not set");
        }
        
        if (false === is_array($request_payload) || false === array_key_exists('method', $request_payload)) {
            throw new RpcException("Invalid RPC request");
        }
        $requestArray = explode('.', $request_payload['method']);
        if (!isset($requestArray[1])) {
            throw new RpcException("Service not specified");
        }
        
        return $requestArray;
    }
}
