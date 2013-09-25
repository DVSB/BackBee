<?php

namespace BackBuilder\Services\Local;

use BackBuilder\Services\Local\AbstractServiceLocal,
    BackBuilder\Theme\PersonalThemesManager,
    BackBuilder\Theme\ThemesManager,
    BackBuilder\Theme\Theme;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Services\Local
 * @copyright   Lp system
 * @author Nicolas BREMONT <nicolas.bremont@lp-digital.fr>
 * @author Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Themes extends AbstractServiceLocal {
    
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

?>
