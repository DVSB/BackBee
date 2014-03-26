<?php

/*
 * Copyright (c) 2011-2014 Lp digital system
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
 */

namespace BackBuilder\Util\Sequence;

/**
 * Sequence repository
 * Utility class providing db stored sequences
 *
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @subpackage  Sequence
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Sequencer extends \Doctrine\ORM\EntityRepository
{

    /**
     * The table name
     * @var string
     */
    private $_table;

    /**
     * The fieldname of _name
     * @var string
     */
    private $_name;

    /**
     * The fieldname of _value
     * @var string
     */
    private $_value;

    /**
     * Class constructor
     * @param type $em
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->_table = $this->getClassMetadata()->table['name'];
        $this->_name = $this->getClassMetadata()->fieldMappings['_name']['columnName'];
        $this->_value = $this->getClassMetadata()->fieldMappings['_value']['columnName'];
    }

    /**
     * Initiate a new sequence with name $name
     * @param string $name
     * @param int $first
     * @return int
     * @throws \BackBuilder\Exception\InvalidArgumentException Occures if sequence $name already exists
     *                                                         or $first is not a positive integer
     */
    private function _init($name, $first = 0)
    {
        if (null !== $this->find($name)) {
            throw new \BackBuilder\Exception\InvalidArgumentException(sprintf('Sequence with name %s already exists', $name));
        }

        if (false === \BackBuilder\Util\Numeric::isPositiveInteger($first, false)) {
            throw new \BackBuilder\Exception\InvalidArgumentException('Initial value of a sequence must be a positive integer');
        }

        $query = 'INSERT INTO ' . $this->_table . ' (' . $this->_name . ', ' . $this->_value . ') VALUE(:name, :value)';
        $params = array(
            'name' => $name,
            'value' => $first
        );

        $this->getEntityManager()
                ->getConnection()
                ->executeQuery($query, $params);

        return $first;
    }

    /**
     * Update a sequence with name $name
     * @param string $name
     * @param int $first
     * @return int
     * @throws \BackBuilder\Exception\InvalidArgumentException Occures if sequence $name doesn't exist
     *                                                         or $value is not a positive integer
     */
    private function _update($name, $value = 0)
    {
        if (null === $this->find($name)) {
            throw new \BackBuilder\Exception\InvalidArgumentException(sprintf('Unknown sequence with name %s', $name));
        }

        if (false === \BackBuilder\Util\Numeric::isPositiveInteger($value, false)) {
            throw new \BackBuilder\Exception\InvalidArgumentException('Initial value of a sequence must be a positive integer');
        }

        $query = 'UPDATE ' . $this->_table . ' SET ' . $this->_value . ' = :value WHERE ' . $this->_name . ' = :name';
        $params = array(
            'name' => $name,
            'value' => $value
        );

        $this->getEntityManager()
                ->getConnection()
                ->executeQuery($query, $params);

        return $value;
    }

    /**
     * Read a sequence with name $name, create it if doesn't exist
     * @param string $name
     * @param int $default
     * @return int
     */
    private function _read($name, $default = 0)
    {
        if (null === $seq = $this->find($name)) {
            return $this->_init($name, $default);
        }

        return $seq->getValue();
    }

    /**
     * Get the next sequence value
     * @param string $name
     * @param int $default
     * @return int
     */
    public function getValue($name, $default = 0)
    {
        $current = $this->_read($name, $default);
        return $this->_update($name, $current + 1);
    }

    /**
     * Update a sequence to $value only if greater than its current value
     * @param string $name
     * @param int $value
     * @return int
     * @throws \BackBuilder\Exception\InvalidArgumentException Occures if $value is not a positive integer
     */
    public function increaseTo($name, $value)
    {
        if (false === \BackBuilder\Util\Numeric::isPositiveInteger($value, false)) {
            throw new \BackBuilder\Exception\InvalidArgumentException('Value of a sequence must be a positive integer');
        }

        $current = $this->_read($name);
        if ($value > $current) {
            return $this->_update($name, $value);
        }

        return $current;
    }

}
