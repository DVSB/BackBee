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

namespace BackBuilder\Cache\IdentifierAppender;

use BackBuilder\Renderer\IRenderer;

/**
 * Every cache identifier appender must implements this interface to be usable
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
interface IdentifierAppenderInterface
{
    /**
     * This method allows every identifier appender to customize cache identifier with its own logic
     *
     * @param string    $identifier the identifier to update if needed
     * @param IRenderer $renderer   the current renderer, can be null
     *
     * @return string return the new identifier
     */
    public function computeIdentifier($identifier, IRenderer $renderer = null);

    /**
     * Returns every group name this appender is associated with
     *
     * @return array
     */
    public function getGroups();
}
