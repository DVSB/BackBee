<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Rest\Hydration;

/**
 * HydrationException
 */
class HydrationException extends \Exception
{
    protected $property;

    /**
     *
     * @param string     $property - the property that was being set when this exception was raised
     * @param \Exception $previous
     */
    public function __construct($property, \Exception $previous = null)
    {
        $this->property = $property;
        parent::__construct("Error occured while setting property \"$property\"", null, $previous);
    }

    public function getProperty()
    {
        return $this->property;
    }
}
