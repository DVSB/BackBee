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

namespace BackBuilder\Importer\Connector\DataBase;

use PDOStatement;

/**
 * PDOStatement wrapper
 * 
 * Implements Countable interface
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @subpackage  Connector
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class PDOResult implements \Countable, \IteratorAggregate
{
    /**
     *
     * @var PDOStatement
     */
    protected $statement;
    
    /**
     * 
     * @param PDOStatement $statement
     */
    public function __construct(PDOStatement $statement) 
    {
        $this->statement = $statement;
    }
    
    /**
     * 
     * @return PDOStatement
     */
    public function getIterator() {
        return $this->statement;
    }
    
    /**
     * Get result count
     * 
     * @return int
     */
    public function count()
    {
        return $this->statement->rowCount();
    }

}