<?php
namespace BackBuilder\Renderer\Helper;

use BackBuilder\Renderer\Exception\RendererException;

abstract class AClassContentHelper extends AHelper {
    protected $object;

    public function invoke($instanceOf, $object) {
        if ($object === null) {
            $this->object = $this->_renderer->getObject();
        } else {
            $this->object = $object;
        }
        if (!is_a($this->object, $instanceOf)) {
            throw new RendererException('The current variable must be an instance of '.$instanceOf, RendererException::HELPER_ERROR);
        }
        return $this;
    }
    
    /**
     * Use invoke for compatibility php 5.4
     * 
     * @deprecated since version 1.0
     * @param string $instanceOf
     * @param object $object
     * @return AClassContentHelper
     */
    public function __invoke($instanceOf, $object) {
        return $this->invoke($instanceOf, $object);
    }

    protected function getObjectParameters($key) {
        $object_params = $this->object->getParam($key);
        if (is_array($object_params) && array_key_exists("array", $object_params)) {
            return $object_params["array"];
        }
        throw new RendererException('There is no param in '.$key, RendererException::HELPER_ERROR);
    }

    protected function getParameterByKey($parameter_key, $needed_keys) {
        $params = $this->getObjectParameters($parameter_key);
        $param_keys = array_keys($params);
        $array_diff = array_diff($needed_keys, $param_keys);
        if (empty($array_diff)) {
            return $params;
        }
        throw new RendererException($parameter_key.' params is not correctly formed', RendererException::HELPER_ERROR);
    }
}