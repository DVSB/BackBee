<?php

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

namespace BackBuilder\Theme;

use Symfony\Component\Yaml\Exception\ParseException,
    Symfony\Component\Yaml\Yaml;
use BackBuilder\Theme\Exception\ThemeException;
use BackBuilder\Util\Dir;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Theme
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
abstract class AThemesManager implements IThemesManager
{
    /**
     * Default theme config file name
     */

    const CONFIG_FILE = 'config.yml';
    const DIRECTORY_MODE = 0777;

    /**
     * theme path
     * 
     * @var string
     */
    protected $_path;

    /**
     * Object constructor.
     *
     * @param string $path_theme
     */
    public function __construct($path_theme)
    {
        $this->_path = $path_theme . DIRECTORY_SEPARATOR;
    }

    /**
     * Update the config.yml
     *
     * @param \BackBuilder\Theme\ThemeEntity $theme
     */
    public function updateConfig(IThemeEntity $theme)
    {
        return file_put_contents(
                        $this->_path . $theme->getFolder() . DIRECTORY_SEPARATOR . self::CONFIG_FILE, Yaml::dump($theme->toArray())
        );
    }

    /**
     * Delete the theme specified
     *
     * @param string $name
     * @return boolean
     */
    public function delete($name)
    {
        return Dir::delete($this->_path . $name);
    }

    /**
     * rename the theme.
     *
     * @param string $name current name
     * @param string $new_name new name
     * @return boolean
     * @throws ThemeException
     */
    public function rename($name, $new_name)
    {
        if (!file_exists($this->_path . $new_name)) {
            Dir::move($this->_path . $name, $this->_path . $new_name, self::DIRECTORY_MODE, array($this, 'renameFolderInConfig', array('name' => $name, 'new_name' => $new_name)));
        } else {
            throw new ThemeException('Theme already exist', ThemeException::THEME_ALREADY_EXISTANT);
        }
    }

    /**
     * Create a copy of the theme.
     *
     * @param string $name name of the source
     * @param string $cp_name destination name
     * @return boolean
     * @throws ThemeException
     */
    public function copy($name, $cp_name)
    {
        if (!file_exists($this->_path . $cp_name)) {
            Dir::copy($this->_path . $name, $this->_path . $cp_name);
            $theme = $this->getTheme($name);
            $theme->setFolder($cp_name);
            $this->updateConfig($theme);
        } else {
            throw new ThemeException('Theme already exist', ThemeException::THEME_ALREADY_EXISTANT);
        }
    }

    /**
     * Change the name folder inside the theme object.
     *
     * @param array $params array containing the old and new name
     * @codeCoverageIgnore
     */
    public function renameFolderInConfig($params)
    {
        $theme = $this->getTheme($params['name']);
        $theme->setFolder($params['new_name']);
        $this->updateConfig($theme);
    }

    /**
     * Return the configuration of the specified theme
     *
     * @param string $name
     * @return BackBuilder\Theme\Theme
     * @throws ThemeException
     */
    public function getTheme($name)
    {
        if (is_file($this->_path . $name . DIRECTORY_SEPARATOR . self::CONFIG_FILE)) {
            $theme_config = $this->_readConfigFile($this->_path . $name . DIRECTORY_SEPARATOR . self::CONFIG_FILE);
        } else {
            throw new ThemeException('Theme does ' . $name . ' not exist', ThemeException::THEME_NOT_FOUND);
        }
        return $this->hydrateTheme($theme_config);
    }

    /**
     * Return all the themes inside the path set in the constructor
     *
     * @return array
     */
    public function getThemesCollection()
    {
        $files = Dir::getContent($this->_path);
        $collection = array();
        foreach ($files as $file) {
            if (is_dir($this->_path . $file) && is_file($this->_path . $file . DIRECTORY_SEPARATOR . self::CONFIG_FILE)) {
                $theme_config = $this->_readConfigFile($this->_path . $file . DIRECTORY_SEPARATOR . self::CONFIG_FILE);
                $collection[] = $this->hydrateTheme($theme_config);
            }
        }

        return $collection;
    }

    /**
     * Read the template config file.
     *
     * @param string $config the config path
     * @return array
     * @throws ParseException
     * @codeCoverageIgnore
     */
    protected function _readConfigFile($config)
    {
        try {
            $datas = Yaml::parse($config);
            return $datas;
        } catch (ParseException $e) {
            throw new ConfigException($e->getMessage(), ConfigException::UNABLE_TO_PARSE, $e, $e->getParsedFile(), $e->getParsedLine());
        }
    }

    /**
     * return the theme folder.
     *
     * @return string
     */
    public function getThemeFolder()
    {
        return $this->_path;
    }

}