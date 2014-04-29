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

namespace BackBuilder\Bundle;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author e.chau <eric.chau@lp-digital.fr>
 *
 * @Table(name="registry", indexes={@index(name="IDX_KEY_SCOPE", columns={"key", "scope"})})
 * @Entity(repositoryClass="BackBuilder\Bundle\Registry\Repository"))
 */
class Registry
{
    /**
     * @var integer
     * 
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var integer
     * 
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $type;

    /**
     * @var string
     *
     * @Column(name="`key`", type="string", length=255)
     */
    protected $key;

    /**
     * @var string
     * 
     * @Column(name="`value`", type="text")
     */
    protected $value;

    /**
     * @var string
     * 
     * @Column(name="`scope`", type="string", length=255)
     */
    protected $scope;


    /**
     * Gets the value of id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the value of key.
     *
     * @return string
     */
    public function getType()
    {
        return $this->objectid;
    }
    
    /**
     * Sets the value of key.
     *
     * @param string $key the key
     *
     * @return self
     */
    public function setType($id)
    {
        $this->objectid = $id;

        return $this;
    }

    /**
     * Gets the value of key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
    
    /**
     * Sets the value of key.
     *
     * @param string $key the key
     *
     * @return self
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Gets the value of value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Sets the value of value.
     *
     * @param string $value the value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets the value of scope.
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }
    
    /**
     * Sets the value of scope.
     *
     * @param string $scope the scope
     *
     * @return self
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }
}
