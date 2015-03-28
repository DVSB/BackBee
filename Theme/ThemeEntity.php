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

namespace BackBee\Theme;

/**
 * @category    BackBee
 * @package     BackBee\Theme
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class ThemeEntity extends AbstractThemeEntity
{
    /**
     * object constructor
     *
     * @param array $values
     */
    public function __construct(array $values = null)
    {
        if (!is_null($values) && is_array($values)) {
            foreach ($values as $key => $value) {
                $this->{'set'.ucfirst($key)}($value);
            }
        }
    }

    /**
     * transform the current object in array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            $this->_name.'_theme' => array(
                'name' => $this->_name,
                'description' => $this->_description,
                'screenshot' => $this->_screenshot,
                'folder' => $this->_folder_name,
                'architecture' => (array) $this->getArchitecture(),
            ),
        );
    }
}
