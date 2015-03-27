<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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

namespace BackBee\Renderer\Helper;

use BackBee\Renderer\Exception\RendererException;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
abstract class AClassContentHelper extends AHelper
{
    protected $object;

    public function invoke($instanceOf, $object)
    {
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
     * Use invoke for compatibility php 5.4.
     *
     * @deprecated since version 1.0
     *
     * @param string $instanceOf
     * @param object $object
     *
     * @return AClassContentHelper
     */
    // public function __invoke($instanceOf, $object)
    // {
    //     return $this->invoke($instanceOf, $object);
    // }

    protected function getObjectParameters($key)
    {
        $object_params = $this->object->getParam($key);
        if (is_array($object_params) && array_key_exists("array", $object_params)) {
            return $object_params["array"];
        }
        throw new RendererException('There is no param in '.$key, RendererException::HELPER_ERROR);
    }

    protected function getParameterByKey($parameter_key, $needed_keys)
    {
        $params = $this->getObjectParameters($parameter_key);
        $param_keys = array_keys($params);
        $array_diff = array_diff($needed_keys, $param_keys);
        if (empty($array_diff)) {
            return $params;
        }
        throw new RendererException($parameter_key.' params is not correctly formed', RendererException::HELPER_ERROR);
    }
}
