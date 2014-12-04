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

namespace BackBuilder\Validator;

/**
 * Validator
 *
 * @category    BackBuilder
 * @package     BackBuilder\Validator
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
abstract class AValidator
{
    const CONFIG_PARAMETER_VALIDATOR = 'validator';
    const CONFIG_PARAMETER_ERROR = 'error';
    const CONFIG_PARAMETER_PARAMETERS = 'parameters';
    const CONFIG_PARAMETER_SET_EMPTY = 'set_empty';
    const CONFIG_PARAMETER_MANDATORY = 'mandatory';

    /**
     * Validate all datas with config
     *
     * @param mixed  $owner
     * @param array  $datas
     * @param array  $errors
     * @param array  $config
     * @param string $prefix
     */
    abstract public function validate($owner, array $datas = array(), array &$errors = array(), array $config = array(), $prefix = '');

    /**
     * Truncate prefix of data keys
     * @param  array  $datas
     * @param  string $prefix
     * @return array
     */
    public function truncatePrefix($datas, $prefix = '')
    {
        $array = array();
        $callback = function ($value, $key) use (&$array, $prefix) {

            $key = preg_replace('/'.$prefix.'/', '', $key, 1);
            $array[$key] = $value;
        };

        array_walk($datas, $callback);

        return $array;
    }

    /**
     * Do general validator
     *
     * @param array   $data
     * @param string  $key
     * @param string  $validator
     * @param array   $config
     * @param array   $errors
     * @param mixed   $func
     * @param boolean $start
     */
    public function doGeneralValidator($data, $key, &$validator, $config, &$errors, &$func = null, $start = false)
    {
        $parameters = array();
        if (true === isset($config[self::CONFIG_PARAMETER_PARAMETERS])) {
            $parameters = $config[self::CONFIG_PARAMETER_PARAMETERS];
        }
        if (null === $func) {
            $func = call_user_func_array(array('Respect\Validation\Validator', $validator), $parameters);
        } else {
            $func = call_user_func_array(array($func, $validator), $parameters);
        }
        if (true === isset($config[self::CONFIG_PARAMETER_VALIDATOR])) {
            $cConfig = $config[self::CONFIG_PARAMETER_VALIDATOR];
            foreach ($cConfig as $sub_validator => $sub_validator_conf) {
                $this->doGeneralValidator($data, $key, $sub_validator, $sub_validator_conf, $errors, $func, true);
                break;
            }
        }

        if (false === $start) {
            $validate = call_user_func(array($func, 'validate'), $data);
            if (false === $validate) {
                $errors[$key] = $config[self::CONFIG_PARAMETER_ERROR];
            }
        }
    }
}
