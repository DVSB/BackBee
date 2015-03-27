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

use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\Yaml\Yaml;

use BackBee\Bundle\Registry;
use BackBee\Job\AbstractJob;

/**
 * A Registry based queue for jobs
 *
 * @category    BackBee
 * @package     BackBee\Job
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class RegistryQueue extends AbstractQueue
{
    /**
     *
     * @var array
     */
    private $jobs;

    private $em;

    /**
     * @return AbstractJob[]
     */
    public function getJobs($status = null)
    {
        $qb = $this->em->getRepository('BackBee\Bundle\Registry')->createQueryBuilder('r');

        // only get jobs that belong to this queue
        $qb->andWhere('r.scope = :queueName');
        $qb->setParameter('queueName', 'JOB.'.$this->getName());

        if (null !== $status) {
            // status is stored within key which is a yaml-encoded string. ugly hack I know :(
            $qb->andWhere('r.key LIKE :status');
            $qb->setParameter('status', '%status: '.$status.'%');
        }

        $registryItems = $qb->getQuery()->execute();

        $jobs = array();

        // convert registry items to jobs
        foreach ($registryItems as $registry) {
            $job = $this->convertRegistryToJob($registry);
            $jobs[] = $job;
        }

        return $jobs;
    }

    //public function set

    /**
     * @return AbstractJob[]
     */
    public function getManagedJobs()
    {
        return $this->jobs;
    }

    /**
     *
     * @param \BackBee\Job\AbstractJob $job
     */
    public function enqueue(AbstractJob $job)
    {
        $registry = $this->convertJobToRegistry($job);

        $this->em->persist($registry);
        $this->em->flush();

        $job->args['registry'] = $registry;

        $this->jobs[] = $job;
    }

    protected function convertRegistryToJob(Registry $registry)
    {
        $jobClass = $registry->getType();
        $job = new $jobClass();

        if (method_exists($job, 'setEntityManager')) {
            $job->setEntityManager($this->em);
        }

        $args = Yaml::parse($registry->getKey());

        $job->status = $args['status'];
        unset($args['status']);
        $job->args = $args;
        $job->args['registry'] = $registry;
        $job->queue = $this->getName();

        return $job;
    }

    protected function convertJobToRegistry(AbstractJob $job)
    {
        if (isset($job->args['registry'])) {
            $registry = $job->args['registry'];
        } else {
            $registry = new Registry();
        }

        $registry->setScope('JOB.'.$this->getName());
        $registry->setType(get_class($job));

        // key - save the job args + status
        $args = $job->args;
        if (isset($args['registry'])) {
            unset($args['registry']);
        }
        $args['status'] = $job->status;
        $registry->setKey(Yaml::dump($args, 0));

        // value - set the date when job was created
        if (!$registry->getValue()) {
            $registry->setValue(date('Y-m-d H:i:s'));
        }

        return $registry;
    }

    public function setEntityManager($em)
    {
        $this->em = $em;
    }

    /**
     * Runs all jobs
     *
     * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->startAllJobs();
    }

    public function startAllJobs()
    {
        $jobs = $this->getJobs(AbstractQueue::JOB_STATUS_NEW);

        foreach ($jobs as $job) {
            // update the job status  in Registry table
            $job->status = AbstractQueue::JOB_STATUS_RUNNING;

            $registry = $this->convertJobToRegistry($job);
            $this->em->persist($registry);
            $this->em->flush();

            // run the job
            $job->perform();

            // delete registry from the DB when finished
            $this->em->remove($registry);
            $this->em->flush();
        }
    }
}
