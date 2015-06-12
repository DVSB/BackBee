<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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

namespace BackBee\Config\Persistor;

use BackBee\ApplicationInterface;
use BackBee\Config\Config;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
interface PersistorInterface
{
    /**
     *
     * @param ApplicationInterface $application The BackBee application instance
     */
    public function __construct(ApplicationInterface $application);

    /**
     *
     * @param \BackBee\Config\Config $config    The BackBee configuration instance
     * @param array  $config_to_persist The configuration to perist
     *
     * @return boolean returns true if persisting operation succeed
     */
    public function persist(Config $config, array $config_to_persist);
}
