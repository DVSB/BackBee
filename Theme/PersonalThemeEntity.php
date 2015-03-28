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

namespace BackBee\Theme;

/**
 * @category    BackBee
 * @package     BackBee\Theme
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 * @Entity(repositoryClass="BackBee\Theme\Repository\ThemeRepository")
 * @Table(name="theme", indexes={@index(name="site_idx", columns={"site_uid"})})
 */
class PersonalThemeEntity extends AbstractThemeEntity
{
    /**
     * Unique identifier of the object
     * @var string
     * @Id @Column(type="string", name="uid")
     */
    protected $_uid;

    /**
     * Site identifier
     * @var string
     * @Column(type="string", name="site_uid")
     */
    protected $_site_uid;

    /**
     * Name of the theme
     * @var string
     * @Column(type="string", name="name")
     */
    protected $_name;

    /**
     * Name of the theme
     * @var string
     * @Column(type="string", name="description")
     */
    protected $_description;

    /**
     * Graphic representation of the theme
     * @var string
     * @Column(type="string", name="screenshot")
     */
    protected $_screenshot;

    /**
     * Name of the folder theme
     * @var string
     * @column(type="string", name="folder")
     */
    protected $_folder_name;

    /**
     * Architecture of the folder theme
     * @var string
     * @column(type="string", name="architecture")
     */
    protected $_architecture;

    /**
     * the personal theme dependency
     * @var string
     * @column(type="string", name="extend")
     */
    private $_extend;

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
                'dependency' => $this->_extend,
                'architecture' => (array) $this->getArchitecture(),
            ),
        );
    }

    /**
     * return the theme who depend the personal theme
     *
     * @codeCoverageIgnore
     * @return string Theme name
     */
    public function getDependency()
    {
        return $this->_extend;
    }

    /**
     * set the personal theme dependency
     *
     * @codeCoverageIgnore
     * @param string $depend Theme name
     */
    public function setDependency($depend)
    {
        $this->_extend = $depend;
    }
}
