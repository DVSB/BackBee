<?php
namespace BackBuilder\DependencyInjection\Loader;

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

use BackBuilder\DependencyInjection\ContainerInterface;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class PhpArrayLoader
{
    /**
     * [$container description]
     * @var BackBuilder\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * [__construct description]
     * @param ContainerInterface $container [description]
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * [load description]
     * @param  [type] $filepath [description]
     * @return [type]           [description]
     */
    public function load($filepath)
    {
        $dump = null;
        if (true === is_readable($filepath)) {
            $dump = unserialize(file_get_contents($filepath));
        }

        if (null === $dump) {
            throw new \Exception($filepath . ' is not readable.');
        }

        if (false === is_array($dump)) {
            throw new \Exception('Content getted from ' . $filepath . ' is not a valid format (array expected).');
        }

        if (false === array_key_exists('parameters', $dump) || false === array_key_exists('services', $dump)) {
            throw new \Exception();
        }

        $this->loadParameters($dump['parameters']);
        $this->loadServices($dump['services']);
    }

    /**
     * [loadParameters description]
     * @param  array  $parameters [description]
     * @return [type]             [description]
     */
    public function loadParameters(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            $this->container->setParameter($key, $value);
        }
    }

    /**
     * [loadServices description]
     * @param  array  $services [description]
     * @return [type]           [description]
     */
    public function loadServices(array $services)
    {
        foreach ($services as $key => $definition) {
            $this->container->setDefinition($key, $this->buildDefinition($definition));
        }
    }

    /**
     * [buildDefinition description]
     * @param  array  $definition_array [description]
     * @return [type]                   [description]
     */
    public function buildDefinition(array $array)
    {
        $definition = new Definition();
        if (true === array_key_exists('synthetic', $array) && true === $array['synthetic']) {
            $definition->setSynthetic(true);
        } else {
            $this->setDefinitionClass($definition, $array);
            $this->setDefinitionArguments($definition, $array);
            // $this->setDefinitionTags($definition, $array);
            // $this->setDefinitionMethodCalls($definition, $array);
        }

        return $definition;
    }

    /**
     * [setDefinitionClass description]
     * @param Definition $definition [description]
     * @param array      $array      [description]
     */
    public function setDefinitionClass(Definition $definition, array $array)
    {
        if (true === array_key_exists('class', $array)) {
            $definition->setClass($array['class']);
        }
    }

    /**
     * [setDefinitionArguments description]
     * @param Definition $definition [description]
     * @param array      $array      [description]
     */
    public function setDefinitionArguments(Definition $definition, array $array)
    {
        if (true === array_key_exists('arguments', $array)) {
            foreach ($array['arguments'] as $arg) {
                $definition->addArgument($this->convertArgument($arg));
            }
        }
    }

    /**
     * [convertArguement description]
     * @param  [type] $argument [description]
     * @return [type]           [description]
     */
    public function convertArgument($argument)
    {
        if (true === is_string($argument) && '@' === $argument[0]) {
            $argument = new Reference(substr($argument, 1));
        }

        return $argument;
    }
}