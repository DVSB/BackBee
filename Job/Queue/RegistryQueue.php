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

namespace BackBuilder\Job\Queue;

use Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Symfony\Component\Yaml\Yaml;

use BackBuilder\Job\AJob,
    BackBuilder\Bundle\Registry;

/**
 * A Registry based queue for jobs
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Job
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class RegistryQueue extends ContainerAware
{
    const JOB_STATUS_NEW = 'new';
    const JOB_STATUS_RUNNING = 'running';
    
    private $name;
    
    /**
     *
     * @var array
     */
    private $jobs;
    
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
     * @return AJob[]
     */
    public function getJobs($scope)
    {
        return $this->jobs;
    }
    
    
    /**
     * @return AJob[]
     */
    public function getRegistryJobs($queue, $status = self::JOB_STATUS_NEW)
    {
        $em = $this->container->get('em');
        
        $registryItems = $em->getRepository('BackBuilder\Bundle\Registry')->findBy(array(
            'scope'   => $queue,
            'type'    => $status
        ));
        
        $jobs = array();
        
        // convert registry items to jobs
        foreach($registryItems as $registryItem) {
            $jobClass = $registryItem->getType();
            $job = new $jobClass();
            
            if($job instanceof ContainerAwareInterface) {
                $job->setContainer($this->container);
            }
            
            list($nodeId, $first, $delta, $status) = explode('|', $registryItem->getKey());
            
            $job->args = array(
                
            );
        }
        
        return $jobs;
    }
    
    
    /**
     * 
     * @param \BackBuilder\Job\AJob $job
     */
    public function addJob(AJob $job)
    {
        $registryItem = new Registry();
        $registryItem->setScope($job->queue);
        $registryItem->setType(get_class($job));
        
        $args = $job->args;
        $args['status'] = self::JOB_STATUS_NEW;
        $registryItem->setKey(Yaml::dump($args, 0));
        
        $registryItem->setValue(date('Y-m-d H:i:s'));

        $em = $this->container->get('em');
        $em->persist($registryItem);
        $em->flush();
        
        $this->jobs[] = $job;
    }
}