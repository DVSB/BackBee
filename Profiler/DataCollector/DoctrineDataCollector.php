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

namespace BackBuilder\Profiler\DataCollector;

use Doctrine\ORM\Tools\SchemaValidator;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector as BaseCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManager;


use Symfony\Component\DependencyInjection\ContainerAwareInterface,
    Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Profiler Toolbar listener
 *
 * @category    BackBuilder
 * @package     BackBuilder\Profiler
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class DoctrineDataCollector extends BaseCollector implements ContainerAwareInterface
{
    private $container;
    private $invalidEntityCount;

    public function __construct()
    {
    }
    
    /**
     * 
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        parent::collect($request, $response, $exception);

        $errors = array();
        $entities = array();


        $entities['default'] = array();
        /** @var $factory \Doctrine\ORM\Mapping\ClassMetadataFactory */
        $factory = $this->container->get('em')->getMetadataFactory();
        $validator = new SchemaValidator($this->container->get('em'));

        /** @var $class \Doctrine\ORM\Mapping\ClassMetadataInfo */
        foreach ($factory->getLoadedMetadata() as $class) {
            $entities['default'][] = $class->getName();
            $classErrors = $validator->validateClass($class);

            if (!empty($classErrors)) {
                $errors['default'][$class->getName()] = $classErrors;
            }
        }
        

        $this->data['entities'] = $entities;
        $this->data['errors'] = $errors;
    }

    public function getEntities()
    {
        return $this->data['entities'];
    }

    public function getMappingErrors()
    {
        return $this->data['errors'];
    }

    public function getInvalidEntityCount()
    {
        if (null === $this->invalidEntityCount) {
            $this->invalidEntityCount = array_sum(array_map('count', $this->data['errors']));
        }

        return $this->invalidEntityCount;
    }
}
