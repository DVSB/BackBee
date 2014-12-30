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

namespace BackBee\Services\Less;

/**
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Less
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class LessTheme
{
    public static $lessTheme = null;
    public static $lessRootPathTheme = null;
    public static $nodePathBin = null;

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
        ;
    }

    public static function getInstance(\BackBee\BBApplication $bbapp)
    {
        if (!isset(self::$lessTheme)) {
            self::$lessTheme = new \BackBee\Services\Less\LessTheme();
            self::$lessRootPathTheme = $bbapp->getCurrentResourceDir().DIRECTORY_SEPARATOR.'themes';
            self::$nodePathBin = $bbapp->getBaseDir().DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'nodejs';
            // return object
            return self::$lessTheme;
        } else {
            return self::$lessTheme;
        }
    }

    private function copyRec($pathDir, $dest)
    {
        if (false === is_dir($pathDir)) {
            throw new \Exception("path: ".$pathDir." is not a directory");
        }

        if ($opd = opendir($pathDir)) {
            while (($file = readdir($opd)) !== false) {
                if ($file != "." && $file != ".." && $file != ".svn") {
                    if (is_dir($pathDir.DIRECTORY_SEPARATOR.$file)) {
                        if (!is_dir($dest.DIRECTORY_SEPARATOR.$file)) {
                            mkdir($dest.DIRECTORY_SEPARATOR.$file, 0777);
                        }
                        $this->copyRec($pathDir.DIRECTORY_SEPARATOR.$file, $dest.DIRECTORY_SEPARATOR.$file);
                    } else {
                        copy($pathDir.DIRECTORY_SEPARATOR.$file, $dest.DIRECTORY_SEPARATOR.$file);
                    }
                }
            }
            closedir($opd);
        } else {
            throw new \Exception("path directory : ".$pathDir." can not be read or incorrect path");
        }
    }

    public function loadTheme($theme = null)
    {
        if ($theme == null) {
            throw new \Exception("Theme can not be null !");
        }

        $pathToTheme = self::$lessRootPathTheme.DIRECTORY_SEPARATOR.$theme;
        $pathToDestination = self::$lessRootPathTheme.DIRECTORY_SEPARATOR.'default';

        if (is_dir(self::$lessRootPathTheme)) {
            $this->copyRec($pathToTheme, $pathToDestination);

            return "success";
        } else {
            throw new \Exception("Directory of theme: ".$theme." doesn't exist !");
        }
    }

    public function getAllThemes()
    {
        $themes = array();
        $pathThemeRoot = self::$lessRootPathTheme;
        if (false === is_dir($pathThemeRoot)) {
            throw new \Exception("path: ".$pathThemeRoot." is not a directory");
        }

        if ($opd = opendir($pathThemeRoot)) {
            while (($file = readdir($opd)) !== false) {
                if ($file != "." && $file != ".." && $file != ".svn" && $file != "default") {
                    if (is_dir($pathThemeRoot.DIRECTORY_SEPARATOR.$file)) {
                        $themes[] = $file;
                    }
                }
            }
            closedir($opd);
        }

        return $themes;
    }

    public function createNewTheme($theme)
    {
        $pathForNewTheme = self::$lessRootPathTheme.DIRECTORY_SEPARATOR.$theme;
        $pathDefaultTheme = self::$lessRootPathTheme.DIRECTORY_SEPARATOR.'default';

        if (false === is_dir($pathForNewTheme)) {
            mkdir($pathForNewTheme, 0777);
        }

        $this->copyRec($pathDefaultTheme, $pathForNewTheme);

        return $theme;
    }

    public function generateStyle($theme)
    {
        $lesscPathBin = self::$nodePathBin.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'lessc'.DIRECTORY_SEPARATOR.'.bin'.DIRECTORY_SEPARATOR.'lessc';
        $pathToLessThemeSelected = self::$lessRootPathTheme.DIRECTORY_SEPARATOR.$theme.DIRECTORY_SEPARATOR.'less'.DIRECTORY_SEPARATOR.'bootstrap.less';
        $pathToDestStyle = self::$lessRootPathTheme.DIRECTORY_SEPARATOR.$theme.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'bootstrap.css';

        print exec($lesscPathBin.' "'.$pathToLessThemeSelected.'" > "'.$pathToDestStyle.'"');
        //chmod($pathToDestStyle, 0777);
    }
}
