<?php
namespace BackBuilder\DependencyInjection\Dumper;

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

/**
 * This interface define every methods a service should implements to be dumpable by the container
 * 
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class DumpableServiceInterface
{
    /**
     * Dumps current service state so we can restore it later by calling DumpableServiceInterface::restore()
     * with the dump array produced by this method
     * 
     * @return array contains every datas required by this service to be restored at the same state
     */
    public function dump();

    /**
     * Restore current service to the dump's state
     * 
     * @param  array $dump the dump provided by DumpableServiceInterface::dump() from where we can 
     *                     restore current service
     */
    public function restore(array $dump);

    /**
     * @return boolean true if current service is already restored, otherwise false
     */
    public function isRestored();
}
