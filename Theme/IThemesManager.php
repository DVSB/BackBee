<?php
namespace BackBuilder\Theme;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Theme
 * @copyright   Lp system
 * @author      n.dufreche
 */
Interface IThemesManager
{
    /**
     * Create a copy of the theme.
     *
     * @param string $name name of the source
     * @param string $cp_name destination name
     */
    public function copy($name, $cp_name);

    /**
     * create theme folder architecture.
     *
     * @param \BackBuilder\Theme\ThemeEntity $theme
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
     * @param string $name current name
     * @param string $new_name new name
     */
    public function rename($name, $new_name);

    /**
     * Update the config.yml
     *
     * @param \BackBuilder\Theme\IThemeEntity $theme
     */
    public function updateConfig(IThemeEntity $theme);

    /**
     * Generate a theme object
     *
     * @param array $theme_config
     * @return \BackBuilder\Theme\PersonalThemeEntity
     * @throws ThemeException
     */
    public function hydrateTheme(array $theme_config);
}