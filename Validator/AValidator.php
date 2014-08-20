<?php

namespace BackBuilder\Validator;

/**
 * Form's validator
 *
 * @category    BackBuilder\Bundle
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

    /**
     * Validate all datas with config
     * 
     * @param mixed $owner
     * @param array $datas
     * @param array $errors
     * @param array $config
     * @param string $prefix
     */
    public abstract function validate($owner, array $datas = array(), array &$errors = array(), array $config = array(), $prefix = '');

    /**
     * Truncate prefix of data keys 
     * @param array $datas
     * @param string $prefix
     * @return array
     */
    protected function truncatePrefix($datas, $prefix = '')
    {
        return array_combine(str_replace($prefix, '', array_keys($datas)), array_values($datas));
    }

    /**
     * Do general validator
     * 
     * @param array $data
     * @param string $key
     * @param string $validator
     * @param array $config
     * @param array $errors
     * @param mixed $func
     * @param boolean $start
     */
    protected function doGeneralValidator($data, $key, &$validator, $config, &$errors, &$func = null, $start = false)
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
            foreach ($cConfig as $sub_validator => $sub_validator_conf)
            {
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
