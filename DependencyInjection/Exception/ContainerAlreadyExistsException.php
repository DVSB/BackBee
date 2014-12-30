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

namespace BackBee\DependencyInjection\Exception;

use BackBee\DependencyInjection\Container;

/**
 * @category    BackBee
 * @package     BackBee\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ContainerAlreadyExistsException extends \BackBee\Exception\BBException
{
    /**
     * container created by container builder which raise this exception
     *
     * @var BackBee\DependencyInjection\Container
     */
    private $container;

    /**
     * ContainerAlreadyExistsException's constructor
     *
     * @param BackBee\DependencyInjection\Container $container the container created by the container builder
     *                                                         which raise this exception
     */
    public function __construct(Container $container)
    {
        parent::__construct('Current container builder already created a container.');

        $this->container = $container;
    }

    /**
     * Getter of the container created by container builder which raise current exception
     *
     * @return BackBee\DependencyInjection\Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}
