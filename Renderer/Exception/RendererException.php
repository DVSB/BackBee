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

namespace BackBuilder\Renderer\Exception;

use BackBuilder\Exception\BBException;

/**
 * Rendering exception
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @subpackage  Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class RendererException extends BBException
{

    const UNKNOWN_ERROR = 5000;
    const SCRIPTFILE_ERROR = 5001;
    const RENDERING_ERROR = 5002;
    const LAYOUT_ERROR = 5003;
    const HELPER_ERROR = 5004;

    protected $_code = self::UNKNOWN_ERROR;

}