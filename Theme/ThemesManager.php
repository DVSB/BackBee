<?php
namespace BackBuilder\Theme;

use BackBuilder\Theme\ThemeEntity,
    BackBuilder\Theme\Exception\ThemeException;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Theme
 * @copyright   Lp system
 * @author      n.dufreche
 */
class ThemesManager extends AThemesManager
{
    /**
     * create theme folder architecture.
     *
     * @param \BackBuilder\Theme\ThemeEntity $theme
     * @throws ThemeException
     */
    public function create(ThemeEntity $theme)
    {
        if (!file_exists($this->_path . $theme->getFolder())) {
            mkdir($this->_path.$theme->getFolder());
            $this->updateConfig($theme);
            foreach ($theme->getArchitecture() as $folder) {
                mkdir($this->_path.$theme->getFolder() . DIRECTORY_SEPARATOR . $folder);
            }
        } else {
            throw new ThemeException('Theme already exist', ThemeException::THEME_ALREADY_EXISTANT);
        }
    }

    /**
     * Generate a theme object
     *
     * @param array $theme_config
     * @return \BackBuilder\Theme\ThemeEntity
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