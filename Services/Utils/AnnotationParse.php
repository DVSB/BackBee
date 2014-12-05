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

namespace BackBuilder\Services\Utils;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Services
 * @subpackage  Utils
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class AnnotationParse
{
    public static function getAnnotation($object)
    {
        $class_name = get_class($object);
        $class_vars = get_class_vars($class_name);
        $class_methods = get_class_methods($object);
        $class_reflection = new \ReflectionClass($object);

        $schema = array();

        foreach ($class_methods as $method_name) {
            $prop_reflection = $class_reflection->getMethod($method_name);
            $comment = $prop_reflection->getDocComment();
            $comment = preg_replace(',\/\*\*(.*)\*\/,', '$1', $comment);
            $comments = preg_split(',\n,', $comment);

            $key = $val = null;
            $schema[$method_name] = array();

            foreach ($comments as $comment_line) {
                if (preg_match(',@(.*?): (.*),i', $comment_line, $matches)) {
                    $key = $matches[1];
                    $val = $matches[2];

                    $schema[$method_name][trim($key)] = trim($val);
                }
            }
        }

        return $schema;
    }
}
