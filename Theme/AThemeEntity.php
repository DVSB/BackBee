<?php
namespace BackBuilder\Theme;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Theme
 * @copyright   Lp system
 * @author      n.dufreche
 */
abstract class AThemeEntity implements IThemeEntity
{
    /**
     * Unique identifier of the object
     * @var string
     */
    protected $_uid;
    /**
     * Site identifier
     * @var string
     */
    protected $_site_uid;
    /**
     * Name of the theme
     * @var string
     */
    protected $_name;
    /**
     * Name of the theme
     * @var string
     */
    protected $_description;
    /**
     * Graphic representation of the theme
     * @var string
     */
    protected $_screenshot;
    /**
     * Name of the folder theme
     * @var string
     */
    protected $_folder_name;
    /**
     * Architecture of the folder theme
     * @var string
     * @column(type="string", name="architecture")
     */
    protected $_architecture;
 

    /**
     * Return the Unique identifier of the current theme
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Return the site identifier of the current theme
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getSiteUid()
    {
        return $this->_site_uid;
    }

    /**
     * Return the name of the current theme
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the screenshot of the current theme
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getScreenshot()
    {
        return $this->_screenshot;
    }

    /**
     * Return the description of the current theme
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * Return the name of the current theme
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFolder()
    {
        return $this->_folder_name;
    }

    /**
     * Set the theme Unique identifier
     *
     * @param string $uid
     * @return Theme
     * @codeCoverageIgnore
     */
    public function setUid($uid)
    {
        $this->_uid = $uid;
        return $this;
    }

    /**
     * Set the theme site identifier
     *
     * @param string $site_uid
     * @return Theme
     * @codeCoverageIgnore
     */
    public function setSiteUid($site_uid)
    {
        $this->_site_uid = $site_uid;
        return $this;
    }

    /**
     * Set the theme site identifier
     *
     * @param string $site_uid
     * @return Theme
     * @codeCoverageIgnore
     */
    public function setSite_uid($site_uid)
    {
        $this->_site_uid = $site_uid;
        return $this;
    }

    /**
     * Set the theme name
     *
     * @param string $name
     * @return Theme
     * @codeCoverageIgnore
     */
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * Set the theme description
     *
     * @param string $description
     * @return Theme
     * @codeCoverageIgnore
     */
    public function setDescription($description)
    {
        $this->_description = $description;
        return $this;
    }

    /**
     * Set the theme grafic representation
     *
     * @param string $file
     * @return Theme
     * @codeCoverageIgnore
     */
    public function setScreenshot($file)
    {
        $this->_screenshot = $file;
        return $this;
    }

    /**
     * Set the theme folder name
     *
     * @param string $folder_name
     * @return Theme
     * @codeCoverageIgnore
     */
    public function setFolder($folder_name)
    {
        $this->_folder_name = $folder_name;
        return $this;
    }

     /**
     * Return the theme architecture
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getArchitecture()
    {
        return (array)json_decode($this->_architecture);
    }

    /**
     * Set the theme architecture
     *
     * @param array $folder_architecture
     * @return Theme
     * @codeCoverageIgnore
     */
    public function setArchitecture(array $folder_architecture)
    {
        $this->_architecture = json_encode($folder_architecture);
        return $this;
    }
}