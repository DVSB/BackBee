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

namespace BackBee\Theme;

use BackBee\Theme\Exception\ThemeException;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class PersonalThemesManager extends AbstractThemesManager
{
    /**
     * create personal theme folder architecture.
     *
     * @param \BackBee\Theme\ThemeEntity $theme ThemeEntity object
     *
     * @throws ThemeException
     */
    public function create(ThemeEntity $theme)
    {
        if (!file_exists($this->_path.$theme->getFolder())) {
            mkdir($this->_path.$theme->getFolder(), 0755, true);
            $this->updateConfig($theme);
            $architecture = $this->_cleanArchitecture($theme->getArchitecture());
            foreach ($architecture as $folder) {
                mkdir($this->_path.$theme->getFolder().DIRECTORY_SEPARATOR.$folder);
            }
        } else {
            throw new ThemeException('Theme already exist', ThemeException::THEME_ALREADY_EXISTANT);
        }
    }

    /**
     * Transform theme architecture into personal theme architecture.
     *
     * @param array $architecture theme architecture
     *
     * @return array personal theme architure
     */
    private function _cleanArchitecture(array $architecture)
    {
        $clean_architecture = array();
        if (array_key_exists('img_dir', $architecture)) {
            $clean_architecture['img_dir'] = $architecture['img_dir'];
        }
        if (array_key_exists('less_dir', $architecture)) {
            $clean_architecture['less_dir'] = $architecture['less_dir'];
        }

        return $clean_architecture;
    }

    /**
     * Generate a theme object.
     *
     * @param array $theme_config
     *
     * @return \BackBee\Theme\PersonalThemeEntity
     *
     * @throws ThemeException
     */
    public function hydrateTheme(array $theme_config)
    {
        $key_valid = array('name', 'description', 'folder', 'dependency');
        if (count(array_diff($key_valid, array_keys(reset($theme_config)))) == 0) {
            $theme = new PersonalThemeEntity(reset($theme_config));

            return $theme;
        } else {
            throw new ThemeException('Theme config is incompleted', ThemeException::THEME_CONFIG_INCORRECT);
        }
    }
}
