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

/**
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Bundle extends AbstractServiceLocal
{
    /**
     * @exposed(secured=true)
     *
     */
    public function findAll()
    {
        if (null === $this->bbapp) {
            throw new ServicesException("None BackBee Application provided", ServicesException::UNDEFINED_APP);
        }

        $result = array();
        foreach ($this->bbapp->getBundles() as $bundle) {
            try {
                $this->isGranted('EDIT', $bundle);
                $result[] = json_decode($bundle->serialize());
            } catch (\BackBee\Security\Exception\ForbiddenAccessException $e) {
                continue;
            }
        }

        return $result;
    }
}
