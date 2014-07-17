<?php
namespace BackBuilder\Config\Exception;

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
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class InvalidConfigTypeException extends \BackBuilder\Exception\BBException
{
    /**
     * InvalidConfigTypeException's constructor
     *
     * @param string $method       method of ConfigBuilder which raise this exception
     * @param string $invalid_type the invalid type provided by user
     */
    public function __construct($method, $invalid_type)
    {
        parent::__construct(sprintf(
            'You provided invalid type (:%s) for Config\ConfigBuilder::%s(). Only %s and %s are supported.',
            $invalid_type,
            $method,
            '0 (=Config\ConfigBuilder::APPLICATION_CONFIG)',
            '1 (=Config\ConfigBuilder::BUNDLE_CONFIG)'
        ));
    }
}
