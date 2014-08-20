<?php

namespace BackBuilder\Validator;

use BackBuilder\Validator\AValidator;

/**
 * ArrayValidator's validator
 *
 * @category    BackBuilder\Bundle
 * @package     BackBuilder\Validator
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class ArrayValidator extends AValidator
{
    const DELIMITER = '__';
    
    /**
     * Validate all datas with config
     * 
     * @param array $array
     * @param array $datas
     * @param array $errors
     * @param array $form_config
     * @param string $prefix
     * @return array
     */
    public function validate($array, array $datas = array(), array &$errors = array(), array $form_config = array(), $prefix = '')
    {
        foreach ($datas as $key => $data)
        {
            if (null !== $cConfig = $this->getData($key, $form_config)) {
                if (true === isset($cConfig[self::CONFIG_PARAMETER_VALIDATOR])) {
                    foreach ($cConfig[self::CONFIG_PARAMETER_VALIDATOR] as $validator => $validator_conf) {
                        $this->doGeneralValidator($data, $key, $validator, $validator_conf, $errors);
                    }
                }

                $do_set = true;
                if (true === isset($cConfig[self::CONFIG_PARAMETER_SET_EMPTY])) {
                    if (false === $cConfig[self::CONFIG_PARAMETER_SET_EMPTY] && true === empty($data)) {
                        $do_set = false;
                    }
                }
                if (true === $do_set) {
                    $this->setData($key, $data, $array);
                }
            }
        }
        
        return $array;
    }
    
    /**
     * Get data to array
     * 
     * @param string $key
     * @param array $array
     * @return null|string
     */
    private function getData($key, $array)
    {
        $matches = explode(self::DELIMITER, $key);
        if (count($matches) > 0) {
            foreach ($matches as $match) {
                if (true === isset($array[$match])) {
                    $array = $array[$match];
                } else {
                    $array = null;
                    break;
                }
            }
        }
   
        return $array;
    }
    
    /**
     * Set data to array
     * 
     * @param string $key
     * @param string $value
     * @param array $array
     */
    private function setData($key, $value, &$array)
    {
        $matches = explode(self::DELIMITER, $key);
        
        $target = &$array;
        while ($index = array_shift($matches)) {
            $target = &$target[$index];
            if (false === is_array($target)) {
                break;
            }
        }
        $target = $value;
    }
}

