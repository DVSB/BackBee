<?php
namespace BackBuilder\Theme;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Theme
 * @copyright   Lp system
 * @author      n.dufreche
 */
class ThemeEntity extends AThemeEntity
{
    /**
     * object constructor
     *
     * @param array $values
     */
    public function __construct(array $values = null)
    {
        if (!is_null($values) && is_array($values)) {
            foreach ($values as $key => $value) {
                $this->{'set'.ucfirst($key)}($value);
            }
        }
    }

    /**
     * transform the current object in array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            $this->_name.'_theme' => array(
                'name' => $this->_name,
                'description' => $this->_description,
                'screenshot' => $this->_screenshot,
                'folder' => $this->_folder_name,
                'architecture' => (array)$this->getArchitecture()
            )
        );
    }
}