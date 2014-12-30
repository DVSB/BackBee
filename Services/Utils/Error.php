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

namespace BackBee\Services\Utils;

/**
 * Description of Error
 *
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Utils
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class Error
{
    const EXCEPTION_CODE = 1000;
    const BB_EXCEPTION_CODE = 1001;
    const RCP_EXCEPTION_CODE = 1002;
    const UPLOAD_EXCEPTION_CODE = 1003;
    const AUTH_EXCEPTION_CODE = 1004;
    const REFLECTION_EXCEPTION_CODE = 1005;
    const AUTOLOAD_EXCEPTION_CODE = 1006;

    public $type = null;
    public $code = null;
    public $message = null;

    public function __construct(\Exception $e)
    {
        $hierarchy = explode(NAMESPACE_SEPARATOR, get_class($e));
        $this->type = array_pop($hierarchy);
        $this->code = $e->getCode();
        $this->message = $e->getMessage();
        $this->trace = $e->getTrace();
    }
}
