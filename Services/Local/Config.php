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

namespace BackBee\Services\Local;

use BackBee\Services\Exception\ServicesException;

/**
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      h.baptiste <harris.baptiste@lp-digital.fr>
 */
class Config extends AbstractServiceLocal
{
    /**
     * @exposed(secured=true)
     */
    public function getRTEConfig()
    {
        try {
            $main_config = $this->bbapp->getConfig();
            $config = $main_config->getSection("rteconfig");

            return $config;
        } catch (\Exception $e) {
            throw new ServicesException("rteConfig.yml file can't be found");
        }
    }
}
