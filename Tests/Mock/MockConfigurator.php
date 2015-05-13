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

namespace BackBee\Tests\Mock;

use BackBee\Config\Config;
use BackBee\Config\Exception\InvalidConfigTypeException;

/**
 * @category    BackBee
 *
 * @copyright   Lp system
 * @author      Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 */
class MockConfigurator extends \BackBee\Config\Configurator implements MockInterface
{
    /**
     * Do every extend according to application context, environment and config target type (APPLICATION OR BUNDLE).
     *
     * @param integer $type    define which kind of extend we want to apply
     *                         (self::APPLICATION_CONFIG or self::BUNDLE_CONFIG)
     * @param Config  $config  the config we want to extend
     * @param array   $options options for extend config action
     */
    public function extend($type, Config $config, $options = array())
    {
        if (false === $config->isRestored()) {
            if (self::APPLICATION_CONFIG === $type) {
                return parent::extend($type, $config, $options);
            } elseif (self::BUNDLE_CONFIG === $type) {
                // do nothing
            } else {
                throw new InvalidConfigTypeException('extend', $type);
            }
        }
    }
}
