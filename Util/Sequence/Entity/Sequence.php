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

namespace BackBee\Util\Sequence\Entity;

/**
 * Sequence Entity.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @Entity(repositoryClass="BackBee\Util\Sequence\Sequencer")
 * @Table(name="sequence")
 */
class Sequence
{
    /**
     * Name of the sequence.
     *
     * @var string
     * @Id @Column(name="name", type="string", nullable=false)
     */
    private $_name;

    /**
     * Sequence.
     *
     * @var string
     * @Column(name="value", type="integer", nullable=false)
     */
    private $_value;

    /**
     * Returns the sequence value.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getValue()
    {
        return $this->_value;
    }
}
