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

use BackBee\Theme\Exception\ThemeException;

/**
 * @category    BackBee
 * @package     BackBee\Theme
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class ThemesManager extends AbstractThemesManager
{
    /**
     * create theme folder architecture.
     *
     * @param  \BackBee\Theme\ThemeEntity $theme
     * @throws ThemeException
     */
    public function create(ThemeEntity $theme)
    {
        if (!file_exists($this->_path.$theme->getFolder())) {
            mkdir($this->_path.$theme->getFolder());
            $this->updateConfig($theme);
            foreach ($theme->getArchitecture() as $folder) {
                mkdir($this->_path.$theme->getFolder().DIRECTORY_SEPARATOR.$folder);
            }
        } else {
            throw new ThemeException('Theme already exist', ThemeException::THEME_ALREADY_EXISTANT);
        }
    }

    /**
     * Generate a theme object
     *
     * @param  array                      $theme_config
     * @return \BackBee\Theme\ThemeEntity
     * @throws ThemeException
     */
    public function hydrateTheme(array $theme_config)
    {
        $key_valid = array('name', 'description', 'folder');

        if (array_key_exists('theme', $theme_config) || count($theme_config) === 1) {
            $theme_config = reset($theme_config);
        }
        if (count(array_diff($key_valid, array_keys($theme_config))) === 0) {
            $theme = new ThemeEntity($theme_config);

            return $theme;
        } else {
            throw new ThemeException('Theme config is incompleted', ThemeException::THEME_CONFIG_INCORRECT);
        }
    }
}
