<?php
namespace BackBuilder\DependencyInjection\Dumper;

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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Dumper\DumperInterface;

/**
 * PhpArrayDumper allow us to dump any container which implements ContainerInterface into
 * php array format;
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class PhpArrayDumper implements DumperInterface
{
    /**
     * container we want to dump to php array format
     *
     * @var BackBuilder\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * PhpArrayDumper's constructor;
     *
     * @param ContainerInterface $container the container we want to dump
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Dumps the service container.
     *
     * @param array $options An array of options
     *
     * @return string The representation of the service container
     */
    public function dump(array $options = array())
    {
        $compiled = false;
        if (true === array_key_exists('do_compile', $options)) {
            $this->container->compile();
            $compiled = true;
        }

        $dumper = array(
            'parameters'    => $this->dumpContainerParameters($options),
            'services'      => $this->dumpContainerDefinitions($options),
            'aliases'       => $this->dumpContainerAliases($options),
            'services_dump' => $this->dumpDumpableServices($options),
            'is_compiled'   => $compiled
        );

        return serialize($dumper);
    }

    /**
     * Dumps every parameters of current container into an array and returns it
     *
     * @param  array  $options
     *
     * @return array contains all parameters of current container
     */
    private function dumpContainerParameters(array $options)
    {
        return $this->container->getParameterBag()->all();
    }

    /**
     * [dumpContainerDefinitions description]
     * @param  array  $options [description]
     * @return [type]          [description]
     */
    private function dumpContainerDefinitions(array $options)
    {
        $definitions = array();
        foreach ($this->container->getDefinitions() as $key => $definition) {
            $definitions[$key] = $this->convertDefinitionToPhpArray($definition);
        }

        return $definitions;
    }

    /**
     * [dumpContainerAliases description]
     * @param  array  $options [description]
     * @return [type]          [description]
     */
    private function dumpContainerAliases(array $options)
    {
        $aliases = array();
        foreach ($this->container->getAliases() as $id => $alias) {
            $aliases[$id] = $alias->__toString();
        }

        return $aliases;
    }

    /**
     * [convertDefinitionToPhpArray description]
     * @param  Definition $definition [description]
     * @return [type]                 [description]
     */
    private function convertDefinitionToPhpArray(Definition $definition)
    {
        $definition_array = array();
        if (true === $definition->isSynthetic()) {
            $definition_array = $this->convertSyntheticDefinitionToPhpArray($definition);
        }

        $this->hydrateDefinitionClass($definition, $definition_array);
        $this->hydrateDefinitionArguments($definition, $definition_array);
        $this->hydrateDefinitionTags($definition, $definition_array);
        $this->hydrateDefinitionMethodCalls($definition, $definition_array);

        return $definition_array;
    }

    /**
     * [convertSyntheticDefinitionToPhpArray description]
     * @param  Definition $definition [description]
     * @return [type]                 [description]
     */
    private function convertSyntheticDefinitionToPhpArray(Definition $definition)
    {
        return array('synthetic' => true);
    }

    /**
     * [hydrateDefinitionClass description]
     * @param  Definition $definition       [description]
     * @param  array      $definition_array [description]
     * @return [type]                       [description]
     */
    private function hydrateDefinitionClass(Definition $definition, array &$definition_array)
    {
        $definition_array['class'] = $definition->getClass();
    }

    /**
     * [hydrateDefinitionArguments description]
     * @param  Definition $definition       [description]
     * @param  array      $definition_array [description]
     * @return [type]                       [description]
     */
    private function hydrateDefinitionArguments(Definition $definition, array &$definition_array)
    {
        foreach ($definition->getArguments() as $arg) {
            $definition_array['arguments'][] = $this->convertArgument($arg);
        }
    }

    /**
     * [convertArgument description]
     * @param  [type] $argument [description]
     * @return [type]           [description]
     */
    private function convertArgument($argument)
    {
        if (is_object($argument) && is_a($argument, 'Symfony\Component\DependencyInjection\Reference')) {
            $argument = '@' . $argument->__toString();
        }

        return $argument;
    }

    /**
     * [hydrateDefinitionTags description]
     * @param  Definition $definition       [description]
     * @param  array      $definition_array [description]
     * @return [type]                       [description]
     */
    private function hydrateDefinitionTags(Definition $definition, array &$definition_array)
    {
        foreach ($definition->getTags() as $key => $tag) {
            $definition_tag = array(
                'name' => $key
            );

            foreach (array_shift($tag) as $key => $option) {
                $definition_tag[$key] = $option;
            }

            $definition_array['tags'][] = $definition_tag;
        }
    }

    /**
     * [hydrateDefinitionMethodCalls description]
     * @param  Definition $definition       [description]
     * @param  array      $definition_array [description]
     * @return [type]                       [description]
     */
    private function hydrateDefinitionMethodCalls(Definition $definition, array &$definition_array)
    {
        foreach ($definition->getMethodCalls() as $method_to_call) {
            $method_call_array = array();

            // retrieving method to call name
            $method_name = array_shift($method_to_call);
            $method_call_array[] = $method_name;

            // retrieving method to call arguments
            $method_args = array();
            foreach (array_shift($method_to_call) as $arg) {
                $method_args[] = $this->convertArgument($arg);
            }

            $method_call_array[] = $method_args;

            // finally add method call to definition array
            $definition_array['calls'][] = $method_call_array;
        }
    }

    private function dumpDumpableServices(array $options)
    {
        $services_dump = array();
        foreach ($this->container->findTaggedServiceIds('dumpable') as $service_id => $data) {
            $services_dump[$service_id] = array(
                'dump'        => $this->container->get($service_id)->dump(),
                'class_proxy' => $class = $this->container->get($service_id)->getClassProxy()
            );
        }

        return $services_dump;
    }
}
