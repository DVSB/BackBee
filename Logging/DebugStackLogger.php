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

namespace BackBuilder\Logging;

/**
 * @category    BackBuilder
 * @package     BackBuilder/Logging
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class DebugStackLogger extends Logger
{
    /**
     * Executed SQL queries.
     *
     * @var array
     */
    public $queries = array();

    /**
     * If Debug Stack is enabled (log queries) or not.
     *
     * @var boolean
     */
    public $enabled = true;

    /**
     * @var float|null
     */
    public $start = null;

    /**
     * @var integer
     */
    public $currentQuery = 0;
    
    
    public function startQuery($sql, array $params = null, array $types = null)
    {
        if ($this->enabled) {
            $this->start = microtime(true);
            $this->queries[++$this->currentQuery] = array('sql' => $sql, 'params' => $params, 'types' => $types, 'executionMS' => 0);
        }
        
        parent:;startQuery($sql, $params, $types);
    }

    public function stopQuery()
    {
        if ($this->enabled) {
            $this->queries[$this->currentQuery]['executionMS'] = microtime(true) - $this->start;
        }
        parent::stopQuery();
    }



}