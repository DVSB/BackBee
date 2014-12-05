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

namespace BackBuilder\DependencyInjection\Exception;

/**
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ServiceNotDumpableException extends \BackBuilder\Exception\BBException
{
    /**
     * ServiceNotDumpableException's constructor
     *
     * @param string      $id    id of the service which is not dumapble
     * @param string|null $class class of the service which is not dumpable (can be null)
     */
    public function __construct($id, $class = null)
    {
        parent::__construct(sprintf(
            'You tagged %s as a dumpable service but it did not implement %s.',
            $id.(null !== $class ? " ($class)" : ''),
            'BackBuilder\DependencyInjection\Dumper\DumpableServiceInterface'
        ));
    }
}
