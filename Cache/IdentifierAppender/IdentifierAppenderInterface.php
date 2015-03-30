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

namespace BackBee\Cache\IdentifierAppender;

use BackBee\Renderer\RendererInterface;

/**
 * Every cache identifier appender must implements this interface to be usable.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
interface IdentifierAppenderInterface
{
    /**
     * This method allows every identifier appender to customize cache identifier with its own logic.
     *
     * @param string    $identifier the identifier to update if needed
     * @param RendererInterface $renderer   the current renderer, can be null
     *
     * @return string return the new identifier
     */
    public function computeIdentifier($identifier, RendererInterface $renderer = null);

    /**
     * Returns every group name this appender is associated with.
     *
     * @return array
     */
    public function getGroups();
}
