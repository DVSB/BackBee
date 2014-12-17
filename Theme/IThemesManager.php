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

namespace BackBee\Theme;

/**
 * @category    BackBee
 * @package     BackBee\Theme
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
interface IThemesManager
{
    /**
     * Create a copy of the theme.
     *
     * @param string $name    name of the source
     * @param string $cp_name destination name
     */
    public function copy($name, $cp_name);

    /**
     * create theme folder architecture.
     *
     * @param \BackBee\Theme\ThemeEntity $theme
     */
    public function create(ThemeEntity $theme);

    /**
     * Delete the theme specified
     *
     * @param string $name
     */
    public function delete($name);

    /**
     * Return the configuration of the specified theme
     *
     * @param string $name
     */
    public function getTheme($name);

    /**
     * Return all the themes inside the path set in the constructor
     */
    public function getThemesCollection();

    /**
     * rename the theme.
     *
     * @param string $name     current name
     * @param string $new_name new name
     */
    public function rename($name, $new_name);

    /**
     * Update the config.yml
     *
     * @param \BackBee\Theme\IThemeEntity $theme
     */
    public function updateConfig(IThemeEntity $theme);

    /**
     * Generate a theme object
     *
     * @param  array                                  $theme_config
     * @return \BackBee\Theme\PersonalThemeEntity
     * @throws ThemeException
     */
    public function hydrateTheme(array $theme_config);
}
