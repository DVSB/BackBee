<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\Services\Rpc\Annotation;

/**
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Rpc\Annotation
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 * @Annotation
 */
class Exposed
{
    private $_secured;

    /**
     * @codeCoverageIgnore
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->_secured = (isset($options["secured"])) ? $options["secured"] : true;
    }

    public function __get($name)
    {
        if ($name === 'secured') {
            return $this->{'_'.$name};
        }

        return;
    }
}
