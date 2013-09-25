<?php
namespace BackBuilder\Theme;

use BackBuilder\Theme\ThemeEntity,
    BackBuilder\Theme\Exception\ThemeException;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Theme
 * @copyright   Lp digital system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class PersonalThemesManager extends AThemesManager
{
    /**
     * create personal theme folder architecture.
     *
     * @param \BackBuilder\Theme\ThemeEntity $theme ThemeEntity object
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
     * Generate a theme object
     *
     * @param array $theme_config
     * @return \BackBuilder\Theme\PersonalThemeEntity
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