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

namespace BackBuilder\Job;

use Symfony\Component\DependencyInjection\ContainerAware;
use BackBuilder\Job\Queue\AQueue;
/**
 * A base class for jobs
 *
 * @category    BackBuilder
 * @package     BackBuilder\Job
 * @copyright   Lp digital system
 * @author      k.golovin
 */
abstract class AJob extends ContainerAware
{
    /**
     * @var array The job args
     */
    public $args = array();

    /**
     * @var string The queue name
     */
    public $queue;

    public $status = AQueue::JOB_STATUS_NEW;

    public function setUp()
    {
    }

    public function perform()
    {
        $this->run($this->args);
    }

    abstract public function run($args);

    public function tearDown()
    {
    }
}
