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

namespace BackBee\Job\Queue;

use BackBee\Job\AbstractJob;

/**
 * Queue Interface
 *
 * @category    BackBee
 * @package     BackBee\Job
 * @copyright   Lp digital system
 * @author      k.golovin
 */
abstract class AbstractQueue
{
    const JOB_STATUS_NEW = 'new';
    const JOB_STATUS_RUNNING = 'running';

    private $name;

    /**
     *
     * @param type string
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \BackBee\Job\AbstractJob $job
     */
    abstract public function enqueue(AbstractJob $job);

    /**
     * @param  string $status
     * @return AbstractJob[]
     */
    abstract public function getJobs($status = null);
}
