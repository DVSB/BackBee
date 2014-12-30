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

namespace BackBee\Cache\Validator;

/**
 * Every cache validator must implements this interface and its methods; every validator must belong to
 * one group atleast which ease user call to cache validator by providing group name to check a set of
 * requirements
 *
 * @category    BackBee
 * @package     BackBee\Cache
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
interface ValidatorInterface
{
    /**
     * Defines if object is candidate for cache processing or not
     *
     * @param mixed $object represents the content we want to apply cache process, can be null
     *
     * @return boolean return true if this object is candidate for cache process, else false
     */
    public function isValid($object = null);

    /**
     * Returns every group name this validator is associated with
     *
     * @return array
     */
    public function getGroups();
}
