<?php
namespace BackBuilder\Util;

class Parameter {
    /**
     * replaces the values of the first array with the same values from all the following arrays.
     * If a key from the first array exists in the second array, its value will be replaced by the value from the second array.
     * If the key exists in the second array, and not the first, it will be deleted.
     * Mutable specify the value of the first array can be overloaded.
     *
     * @access public
     * @param array $array1
     * @param array $array2
     * @param array $mutableField = false
     * @return array the cleaned param
     */
    public static function paramsReplaceRecursive(array $array1, $array2, $mutableField = false) {
        if (!is_array($array2)) {
            return $array1;
        }
        foreach ($array1 as $key => $value) {
            $mutableFields = (is_array($value) && array_key_exists("_mutablefields", $value)) ? (array) $value["_mutablefields"] : $mutableField;

            if (array_key_exists($key, $array2)) {
                if (is_array($value)) {
                    if (is_int(key($value)) || is_null(key($value))){
                        $array1[$key] = $array2[$key];
                    } else {
                        $array1[$key] = self::paramsReplaceRecursive($array1[$key], $array2[$key], $mutableFields);
                    }
                } else {
                    if (!$mutableField) {
                        $array1[$key] = $array2[$key];
                    } else {
                        if (in_array($key, $mutableFields)) {
                            $array1[$key] = $array2[$key];
                        }
                    }
                }
            }
        }
        return $array1;
    }
}