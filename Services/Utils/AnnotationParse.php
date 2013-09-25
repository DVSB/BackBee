<?php

namespace BackBuilder\Services\Utils;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AnnotationParse
 *
 * @author n.bremont
 */
class AnnotationParse {
    
    public static function getAnnotation($object)
    {
        $class_name         = get_class($object);
        $class_vars         = get_class_vars($class_name);
        $class_methods      = get_class_methods($object);
        $class_reflection   = new \ReflectionClass($object);

        $schema = array();

        foreach($class_methods as $method_name)
        {
            $prop_reflection = $class_reflection->getMethod($method_name);
            $comment = $prop_reflection->getDocComment();
            $comment = preg_replace(',\/\*\*(.*)\*\/,', '$1', $comment);
            $comments = preg_split(',\n,', $comment);

            $key = $val = NULL;
            $schema[$method_name] = array();

            foreach($comments as $comment_line)
            {
                if(preg_match(',@(.*?): (.*),i', $comment_line, $matches))
                {
                    $key = $matches[1];
                    $val = $matches[2];

                    $schema[$method_name][trim($key)] = trim($val);
                }
            }
        }
        //var_dump($schema);
        return $schema;
    }
}
?>
