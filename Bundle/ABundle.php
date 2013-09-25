<?php
namespace BackBuilder\Bundle;

use BackBuilder\BBApplication,
    BackBuilder\Config\Config,
    BackBuilder\Routing\RouteCollection as Routing,
    BackBuilder\Logging\Logger;

abstract class ABundle implements \Serializable {
    private $_id;
    private $_application;
    private $_em;
    private $_logger;
    private $_basedir;
    private $_config;
    private $_properties;
    private $_routing;
    
    public function __call($method, $args) {
        if (NULL !== $this->getLogger()) {
            if (true === is_array($args) && 0 < count($args)) {
                $args[0] = sprintf('[%s] %s', $this->getId(), $args[0]);
            }
            
            call_user_func_array(array($this->getLogger(), $method), $args);
        }
    }
    
    public function __construct(BBApplication $application, Logger $logger = null) {
        $this->_application = $application;
        
        // To do : check for a specific EntityManager
        $this->_em = $this->_application->getEntityManager();
        
        $r = new \ReflectionObject($this);
        $this->_basedir = dirname($r->getFileName());
        $this->_id = basename($this->_basedir);
        
        $this->_logger = $logger;
        if (NULL === $this->_logger)
            $this->_logger = $this->_application->getLogging();
    }
    
    private function _initConfig($configdir = null) {
        if (is_null($configdir))
            $configdir = $this->getResourcesDir();

        $this->_config = new Config($configdir);
        
        return $this;
    }

    private function _initRouting() {
        $routing = $this->getConfig()->getRoutingConfig();
        if (is_null($routing))
            $this->_routing = false;

        $this->_routing = new Routing($this->_application);
        $this->_routing->addBundleRouting($this);

        return $this;
    }
    
    /**
     * @return BBApplication
     */
    public function getApplication() {
        return $this->_application;
    }
    
    public function getEntityManager() {
        return $this->_em;
    }
    
    public function getLogger() {
        return $this->_logger;
    }
    
    public function getBaseDir() {
        return $this->_basedir;
    }
    
    public function getResourcesDir() {
        return $this->getBaseDir() . DIRECTORY_SEPARATOR . 'Ressources';
    }
    
    public function getId() {
        return $this->_id;
    }

    public function getRouting() {
        if (NULL === $this->_routing)
            $this->_initRouting();

        return $this->_routing;
    }

    /**
     * Returns the current request
     * @access public
     * @return Request
     */
    public function getRequest() {
        if (NULL === $this->_request)
            $this->_request = Request::createFromGlobals();

        return $this->_request;
    }

    public function getConfig() {
        if (NULL === $this->_config)
            $this->_initConfig();
        
        return $this->_config;
    }
    
    public function getProperty($key = null) {
        if (NULL === $this->_properties) {
            $this->_properties = $this->getConfig()->getSection('bundle');
            if (NULL === $this->_properties)
                $this->_properties = array();
        }
        
        if (NULL === $key)
            return $this->_properties;
        
        if (array_key_exists($key, $this->_properties))
            return $this->_properties[$key];
        
        return NULL;
    }
    
    public function setLogger(Logger $logger) {
        $this->_logger = $logger;
        return $this;
    }
    
    public function serialize() {
        $obj = new \stdClass();
        $obj->id = $this->getId();
        
        foreach($this->getProperty() as $key => $value)
            $obj->$key = $value;
        
        return json_encode($obj);
    }
    
    public function unserialize($serialized) {
        
    }
    
    abstract function init();
    abstract function start();
    abstract function stop();
}