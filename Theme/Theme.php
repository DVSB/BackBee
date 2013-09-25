<?php

namespace BackBuilder\Theme;

use BackBuilder\BBApplication;
use BackBuilder\Theme\Exception\ThemeException;
use BackBuilder\Util\Dir,
    BackBuilder\Util\File;
use BackBuilder\Config\Config;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Theme
 * @copyright   Lp digital system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Theme extends ThemeConst
{

    /**
     * Site identifier
     * @var string
     */
    private $_site_uid;

    /**
     * Path of the theme folder
     * @var array
     */
    private $_themes_dir = array();

    /**
     * Renderer object
     * @var BBApplication
     */
    private $_bbapp;

    /**
     * The template folder is valid (exist ? and is readable ?)
     * @var boolean
     */
    private $_is_valid = false;

    /**
     * Current theme.
     * @var PersonalThemeEntity
     */
    private $_personal_theme;

    /**
     * Current theme.
     * @var ThemeEntity
     */
    private $_theme;

    /**
     * Current theme.
     * @var ThemeEntity
     */
    private $_default_theme;

    /**
     * Directory Entity
     * @var array
     */
    private $_dir = array();

    /**
     * Config
     * @var BackBuilder\Config\Config
     */
    private $_config;

    /**
     * Theme object constructor
     *
     * @param \BackBuilder\BBApplication $bbapp
     * @throws ThemeException
     */
    public function __construct(BBApplication $bbapp = null)
    {
        if ($bbapp == null) {
            throw new ThemeException('Bad contruct implementation', ThemeException::THEME_BAD_CONSTRUCT);
        }
        $this->_bbapp = $bbapp;
        $this->_site_uid = (null !== $bbapp->getSite()) ? $bbapp->getSite()->getUid() : null;
        $this->_themes_dir = $this->_bbapp->getConfig()->getSection('themes_dir');
        $manager = new ThemesManager($this->getThemeDir(self::DEFAULT_NAME));
        $this->_default_theme = $manager->hydrateTheme($this->_bbapp->getConfig()->getSection('theme'));
        $this->parseDirectory($this->_default_theme->getArchitecture(), $this->getDefaultDirectory());
    }

    /**
     * Initialise alls the themes repository with their dependencies.
     */
    public function init()
    {
        $theme_repository = $this->_bbapp->getEntityManager()->getRepository('BackBuilder\Theme\PersonalThemeEntity');
        $this->_personal_theme = $theme_repository->retrieveBySiteUid($this->_site_uid);

        if (is_object($this->_personal_theme) && $this->_personal_theme->getDependency() != self::DEFAULT_NAME) {
            $manager = new ThemesManager($this->getThemeDir(self::THEME_NAME));
            $this->_theme = $manager->getTheme($this->_personal_theme->getDependency());

            if (is_object($this->_theme)) {
                $this->_is_valid = $this->validateDirectory();
            }

            $this->_bbapp->getConfig()->extend($this->getDirectory());
        }

        $this->build();
    }

    /**
     * Builds the selecteds themes for dispatch in backbuilder.
     */
    public function build()
    {
        if ($this->getDefaultDirectory() != $this->getDirectory()) {
            $this->parseDirectory($this->_theme->getArchitecture(), $this->getDirectory());
        }
        if (is_object($this->_personal_theme)) {
            $this->parseDirectory($this->_personal_theme->getArchitecture(), $this->getPersonalDirectory());
        }
    }

    /**
     * Return the base theme folder.
     *
     * @param string $type
     * @return Mixed
     */
    public function getThemeDir($type)
    {
        if (array_key_exists($type, $this->_themes_dir)) {
            $theme_dir = $this->_themes_dir[$type];
            File::resolveFilepath($theme_dir, NULL, array('base_dir' => $this->_bbapp->getBaseDir()));
            return $theme_dir . DIRECTORY_SEPARATOR;
        }
        return false;
    }

    /**
     * Return the path to the current theme if is valid else return the default theme
     *
     * @return string
     */
    public function getDirectory()
    {
        if ($this->_is_valid) {
            $dir = $this->_theme->getFolder();
            File::resolveFilepath($dir, NULL, array('base_dir' => $this->getThemeDir(static::THEME_NAME)));
            return $dir;
        }
        return $this->getDefaultDirectory();
    }

    /**
     * Return the path to the default theme if exist
     *
     * @return Mixed
     */
    public function getDefaultDirectory()
    {
        if (
                is_dir($this->getThemeDir(static::DEFAULT_NAME) . $this->_default_theme->getFolder()) &&
                is_readable($this->getThemeDir(static::DEFAULT_NAME) . $this->_default_theme->getFolder())
        ) {
            return $this->getThemeDir(static::DEFAULT_NAME) . $this->_default_theme->getFolder();
        }
        return false;
    }

    /**
     * Return the path to the default theme if exist
     *
     * @return string
     * @throws ThemeException
     */
    public function getPersonalDirectory()
    {
        $site_folder = $this->_site_uid . DIRECTORY_SEPARATOR . $this->_personal_theme->getFolder();
        if (!is_dir($this->getThemeDir(static::PERSONAL_NAME) . $site_folder)) {
            $sub_folder = mkdir($this->getThemeDir(static::PERSONAL_NAME) . $this->_site_uid, 0700, true);
            $theme_folder = mkdir($this->getThemeDir(static::PERSONAL_NAME) . $site_folder, 0700, true);

            if ($sub_folder && $theme_folder) {
                $manager = new PersonalThemesManager($this->getThemeDir(static::PERSONAL_NAME) . $this->_site_uid);
                $manager->updateConfig($this->_personal_theme);
            } else {
                throw new ThemeException('Folder creation error', ThemeException::THEME_PATH_INCORRECT);
            }
        }
        return $this->getThemeDir(static::PERSONAL_NAME) . $site_folder;
    }

    public function getIncludePath($name)
    {
        if (array_key_exists($name, $this->_dir)) {
            return $this->_dir[$name];
        }
    }

    /**
     * Parse the theme directory to know how kinde of elements the object need refernce
     *
     * @param string $name path to parse
     */
    private function parseDirectory($architecture, $path)
    {
        $files = Dir::getContent($path);
        foreach ($files as $file) {
            $key = in_array($file, $architecture) ? array_search($file, $architecture) : false;
            if ($key) {
                $this->dispatchDirectory($architecture, $path, $key);
            }
        }
    }

    /**
     * Send to the object
     *
     * @param string $path current path
     * @param string $target target you need to dispatch
     */
    private function dispatchDirectory($architecture, $path, $target)
    {
        if ($target === static::SCRIPT_DIR) {
            $this->_bbapp->getRenderer()->addScriptDir($path . DIRECTORY_SEPARATOR . $architecture[$target]);
        } elseif ($target === static::HELPER_DIR) {
            $this->_bbapp->getRenderer()->addHelperDir($path . DIRECTORY_SEPARATOR . $architecture[$target]);
        } elseif ($target === static::LAYOUT_DIR) {
            $this->_bbapp->getRenderer()->addLayoutDir($path . DIRECTORY_SEPARATOR . $architecture[$target]);
        } elseif ($target === static::LISTENER_DIR) {
            $this->_bbapp->getAutoloader()->registerListenerNamespace($path . DIRECTORY_SEPARATOR . $architecture[$target]);
        } elseif (in_array($target, array(static::CSS_DIR, static::LESS_DIR, static::JS_DIR, static::IMG_DIR))) {
            if (!array_key_exists($target, $this->_dir)) {
                $this->_dir[$target] = array();
            }
            array_unshift($this->_dir[$target], $path . DIRECTORY_SEPARATOR . $architecture[$target]);
        }
        return true;
    }

    /**
     * Validate the theme directory
     *
     * @return boolean
     */
    private function validateDirectory()
    {
        if (
                empty($this->_themes_dir[static::THEME_NAME]) ||
                !is_dir($this->getThemeDir(static::THEME_NAME) . $this->_theme->getFolder()) ||
                !is_readable($this->getThemeDir(static::THEME_NAME) . $this->_theme->getFolder())
        ) {
            return false;
        }
        return true;
    }

    /**
     * Init config
     *
     * @return Theme
     */
    private function _initConfig($configdir = null)
    {
        if (is_null($configdir))
            $configdir = $this->getDirectory();

        $this->_config = new Config($configdir);

        return $this;
    }

    /**
     * Get config
     *
     * @return BackBuilder\Config\Config
     */
    public function getConfig()
    {
        if (NULL === $this->_config)
            $this->_initConfig();

        return $this->_config;
    }

}