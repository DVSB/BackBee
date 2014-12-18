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

namespace BackBee\Bundle\Exception;

use BackBee\Exception\BBException;

/**
 * @category    BackBee
 * @package     BackBee\Bundle
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class RequestErrorException extends BBException
{
    /**
     * @var integer
     */
    private $statusCode;

    /**
     * RequestErrorException's constructor
     *
     * @param string  $message
     * @param integer $statusCode
     */
    public function __construct($message, $statusCode)
    {
        parent::__construct($message);

        $this->statusCode = intval($statusCode);
    }

    /**
     * Status code getter
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
