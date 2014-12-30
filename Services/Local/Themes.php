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

namespace BackBee\Services\Local;

use BackBee\Theme\PersonalThemesManager;
use BackBee\Theme\Theme;
use BackBee\Theme\ThemesManager;

/**
 * RPC services for User management
 *
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author Nicolas BREMONT <nicolas.bremont@lp-digital.fr>
 * @author Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Themes extends AbstractServiceLocal
{
    /**
     * @exposed(secured=true)
     */
    public function activeTheme($name)
    {
    }

    private function getManager()
    {
        return new PersonalThemesManager($this->bbapp->getTheme()->getThemeDir(Theme::PERSONAL_NAME));
    }

    /**
     * @exposed(secured=true)
     */
    public function copyTheme($new_name, $name)
    {
        try {
            $manager = $this->getManager();
            $manager->copy($name, $new_name);
        } catch (\Exception $exc) {
            return $exc->getMessage();
        }

        return true;
    }

    /**
     * @exposed(secured=true)
     */
    public function createTheme($name)
    {
        $theme_dir = '';
        if ($name == Theme::DEFAULT_NAME) {
            $theme_dir = $this->bbapp->getTheme()->getThemeDir(Theme::DEFAULT_NAME);
        } else {
            $theme_dir = $this->bbapp->getTheme()->getThemeDir(Theme::THEME_NAME);
        }
        try {
            $manager = new ThemesManager($theme_dir);
            $personal_manager = $this->getManager();
            $personal_manager->create($manager->getTheme($name));
        } catch (\Exception $exc) {
            return $exc->getMessage();
        }

        return true;
    }

    /**
     * @exposed(secured=true)
     */
    public function getThemes()
    {
        $manager = $this->getManager();

        return $manager->getThemesCollection();
    }

    /**
     * @exposed(secured=true)
     */
    public function getTheme($name)
    {
        $manager = $this->getManager();

        return $manager->getTheme($name);
    }
}
