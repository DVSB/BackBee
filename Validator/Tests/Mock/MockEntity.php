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

namespace BackBuilder\Validator\Tests\Mock;

use BackBuilder\Tests\Mock\IMock;

/**
 * Mock entity
 *
 * @category    BackBuilder
 * @package     BackBuilder\Validator\Tests\Mock
 * @copyright   Lp digital system
 * @author      f.kroockmann
 * @Table(name="mock_entity")
 * @Entity()
 */
class MockEntity implements IMock
{
    /**
     * Identifier
     * @var int $id 
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * Numeric code
     * @var int
     * @Column(name="numeric_code", type="integer", unique=true) 
     */
    private $numeric_code;
    
    /**
     * English's name
     * @var string $name 
     * @Column(name="name", type="string", length=100)
     */
    private $name;
    
    /**
     * get id
     * @return int
     */
    public function getId() 
    {
        return $this->id;
    }

    /**
     * get numeric code
     * @return int
     */
    public function getNumericCode() 
    {
        return $this->numeric_code;
    }

    /**
     * get name
     * @return string 
     */
    public function getName() 
    {
        return $this->name;
    }
    
    /**
     * Set numeric code
     * @param int $numeric_code
     * @return \BackBuilder\Validator\Tests\Mock\MockEntity
     */
    public function setNumericCode($numeric_code)
    {
        $this->numeric_code = $numeric_code;
        return $this;
    }
    
    /**
     *  set name
     * @param type $name
     * @return \BackBuilder\Validator\Tests\Mock\MockEntity
     */
    public function setName($name) 
    {
        $this->name = $name;
        return $this;
    }
}