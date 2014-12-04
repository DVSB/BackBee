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

namespace BackBuilder\Logging\Formatter;

/**
 * @category    BackBuilder
 * @package     BackBuilder/Logging
 * @subpackage  Formatter
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Simple implements IFormatter
{
    private $_format = '%d %p [%u]: %m';

    public function __construct($format = null)
    {
        if (NULL !== $format) {
            $this->_format = $format;
        }
    }

    public function format($event)
    {
        $output = $this->_format.PHP_EOL;

        foreach ($event as $key => $value) {
            $output = str_replace('%'.$key, $value, $output);
        }

        return $output;
    }
}
