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

namespace BackBuilder\Util;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Arrays
{

    public static function toCsv($values, $separator = ';')
    {
        $return = '';
        foreach ($values as $value) {
            $return .= implode($separator, $value) . "\n";
        }
        return $return;
    }

    public static function toBasicXml($array)
    {
        $return = '';
        foreach ($array as $key => $value) {
            $return .= '<' . $key . '>';
            if (is_array($value)) {
                $return .= static::toBasicXml($value);
            } else {
                $return .= str_replace('&', '&amp;', $value);
            }
            $return .= '</' . $key . '>';
        }
        return $return;
    }

    /**
     * $exemple = array (
     *     'root' => array(
     *         'params' => array('id' => 1),
     *         'child' => array(
     *             'singleTag' => 123456789,
     *             'multiTags' => array(
     *                 'children' => array(
     *                     'tag' => array(
     *                         array(
     *                             'child' => array(
     *                                 'childrenInnerTag' => '#PC_DATA1'
     *                              )
     *                         ),
     *                         array(
     *                              'child' => array(
     *                                  'childrenInnerTag' => '#PC_DATA2'
     *                              )
     *                         )
     *                     ),
     *                 )
     *             )
     *         )
     *     )
     * );
     *
     * return :
     *
     * <root id="1">
     *     <singleTag>123456789</singleTag>
     *     <multiTags>
     *         <tag>
     *             <childrenTag>#PC_DATA1</childrenTag>
     *         </tag>
     *         <tag>
     *             <childrenTag>#PC_DATA1</childrenTag>
     *         </tag>
     *     </multiTags>
     * </root>
     *
     * @codeCoverageIgnore
     * @param array $array
     * @return string
     */
    public static function toXml(array $array)
    {
        return str_replace('&', '&amp;', self::convertChild($array));
    }

    /**
     * [array_diff_assoc_recursive description]
     *
     * @param  array $array1
     * @param  array $array2
     * @return array
     */
    public static function array_diff_assoc_recursive(array $array1, array $array2)
    {
        $diff = array();
        foreach ($array1 as $key => $value) {
            if (true === is_array($value)) {
                if (false === isset($array2[$key]) || false === is_array($array2[$key])) {
                    $diff[$key] = $value;
                } else {
                    $newDiff = self::array_diff_assoc_recursive($value, $array2[$key]);
                    if (false === empty($newDiff)) {
                        $diff[$key] = $newDiff;
                    }
                }
            } elseif (false === array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * [array_merge_assoc_recursive description]
     *
     * @param  array $array1
     * @param  array $array2
     * @return array
     */
    public static function array_merge_assoc_recursive($array1, $array2)
    {
        foreach ($array2 as $key => $value) {
            if (false === array_key_exists($key, $array1)) {
                $array1[$key] = $value;
            } elseif (true === is_array($value)) {
                $array1[$key] = self::array_merge_assoc_recursive($array1[$key], $array2[$key]);
            } else {
                $array1[$key] = $value;
            }
        }

        return $array1;
    }

    /**
     * [array_remove_assoc_recursive description]
     * @param  [type] $array1
     * @param  [type] $array2
     * @return [type]
     */
    public static function array_remove_assoc_recursive(&$array1, $array2)
    {
        foreach ($array2 as $key => $value) {
            if (false === array_key_exists($key, $array1)) {
                return $array1;
            } elseif (true === is_array($value)) {
                self::array_remove_assoc_recursive($array1[$key], $value);
                if (0 === count($array1[$key])) {
                    unset($array1[$key]);
                }
            } else {
                unset($array1[$key]);
            }
        }
    }

    private static function convertChild(array $array)
    {
        $return = '';
        foreach ($array as $tag => $children) {
            if ($tag == 'child') {
                $return .= self::convertChild($children);
            } elseif ($tag == 'children') {
                $return .= self::convertChildren($children);
            } elseif ($tag == 'params') {
                continue;
            } else {
                $return .= self::getTag($tag, $children);
                $return .= self::getContent($children);
                $return .= '</' . $tag . '>';
            }
        }
        return $return;
    }

    private static function getTag($key, $values)
    {
        $return = '<' . $key;
        if (is_array($values) && array_key_exists('params', $values)) {
            $return .= self::convertParams($values['params']);
        }
        return $return . '>';
    }

    private static function getContent($values)
    {
        if (is_array($values)) {
            return self::convertChild($values);
        } else {
            return $values;
        }
    }

    private static function convertParams(array $array)
    {
        $return = '';
        foreach ($array as $key => $value) {
            $return .= ' ' . $key . '="' . $value . '"';
        }
        return $return;
    }

    private static function convertChildren(array $array)
    {
        $return = '';
        foreach ($array as $tag => $values) {
            foreach ($values as $value) {
                $return .= self::getTag($tag, $value);
                $return .= self::getContent($value);
                $return .= '</' . $tag . '>';
            }
        }
        return $return;
    }

    /**
     * Tests if key:subkey exists in array
     * @param array $array
     * @param string $key
     * @param string $separator
     * @return boolean
     * @throws \BackBuilder\Exception\InvalidArgumentException
     */
    public static function has(array $array, $key, $separator = ':', &$traverse = array())
    {
        if (false === is_string($key)) {
            throw new \BackBuilder\Exception\InvalidArgumentException('$key parameter as to be a string');
        }

        if (false === is_string($separator)) {
            throw new \BackBuilder\Exception\InvalidArgumentException('$separator parameter as to be a string');
        }

        $traverse = $array;
        $keys = explode($separator, $key);
        foreach ($keys as $key) {
            if (false === is_array($traverse) || false === array_key_exists($key, $traverse)) {
                return false;
            }
            $traverse = $traverse[$key];
        }

        return true;
    }

    /**
     * Gets the value of key:subkey in array, NULL othewise
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @param string $separator
     * @return mixed
     * @throws \BackBuilder\Exception\InvalidArgumentException
     */
    public static function get(array $array, $key, $default = null, $separator = ':')
    {
        $traverse = array();
        if (true === self::has($array, $key, $separator, $traverse)) {
            return $traverse;
        }

        return $default;
    }

}
