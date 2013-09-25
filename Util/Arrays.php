<?php

namespace BackBuilder\Util;

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
     * @param array $array
     * @return string
     */
    public static function toXml(array $array)
    {
        return str_replace('&', '&amp;', self::convertChild($array));
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
        $return = '<' .$key;
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
}