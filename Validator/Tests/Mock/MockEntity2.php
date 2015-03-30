<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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

namespace BackBee\Validator\Tests\Mock;

use BackBee\Tests\Mock\MockInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mock entity.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      f.kroockmann
 * @ORM\Table(name="mock_entity2")
 * @ORM\Entity
 */
class MockEntity2 implements MockInterface
{
    /**
     * Identifier.
     *
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * Identifier.
     *
     * @var int
     * @ORM\Column(name="id2", type="integer")
     * @ORM\Id
     */
    private $id2;

    /**
     * Numeric code.
     *
     * @var int
     * @ORM\Column(name="numeric_code", type="integer", unique=true)
     */
    private $numeric_code;

    /**
     * English's name.
     *
     * @var string
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    public function __construct($id, $id2)
    {
        $this->id = $id;
        $this->id2 = $id2;
    }

    /**
     * get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * get id2.
     *
     * @return int
     */
    public function getId2()
    {
        return $this->id2;
    }

    /**
     * get numeric code.
     *
     * @return int
     */
    public function getNumericCode()
    {
        return $this->numeric_code;
    }

    /**
     * get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set numeric code.
     *
     * @param int $numeric_code
     *
     * @return \BackBee\Validator\Tests\Mock\MockInterface
     */
    public function setNumericCode($numeric_code)
    {
        $this->numeric_code = $numeric_code;

        return $this;
    }

    /**
     *  set name.
     *
     * @param type $name
     *
     * @return \BackBee\Validator\Tests\Mock\MockInterface
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
