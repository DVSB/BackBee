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

use BackBuilder\DependencyInjection\Dumper\DumpableServiceInterface;
use BackBuilder\DependencyInjection\Exception\ServiceNotDumpableException;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
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
     * Dumps every container definitions into array
     *
     * @param  array $options
     *
     * @return array contains every container definitions converted to array
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
     * Dumps every container aliases into array
     *
     * @param  array $options
     *
     * @return array contains container aliases
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
     * Convert a single definition entity into array
     *
     * @param  Definition $definition the definition to convert
     *
     * @return array the definition converted into array
     */
    private function convertDefinitionToPhpArray(Definition $definition)
    {
        $definition_array = array();
        if (true === $definition->isSynthetic()) {
            $definition_array = $this->convertSyntheticDefinitionToPhpArray($definition);
        }

        $this->hydrateDefinitionClass($definition, $definition_array);
        $this->hydrateDefinitionArguments($definition, $definition_array);
        $this->hydrateDefinitionFactoryClass($definition, $definition_array);
        $this->hydrateDefinitionFactoryMethod($definition, $definition_array);
        $this->hydrateDefinitionFactoryService($definition, $definition_array);
        $this->hydrateDefinitionTags($definition, $definition_array);
        $this->hydrateDefinitionMethodCalls($definition, $definition_array);
        $this->hydrateDefinitionConfigurator($definition, $definition_array);
        $this->hydrateDefinitionParent($definition, $definition_array);
        $this->hydrateDefinitionScopeProperty($definition, $definition_array);
        $this->hydrateDefinitionPublicProperty($definition, $definition_array);
        $this->hydrateDefinitionAbstractProperty($definition, $definition_array);
        $this->hydrateDefinitionFileProperty($definition, $definition_array);

        return $definition_array;
    }

    /**
     * Convert a synthetic definition entity into a synthetic definition array
     *
     * @param  Definition $definition the definition to convert
     *
     * @return array the synthetic definition array
     */
    private function convertSyntheticDefinitionToPhpArray(Definition $definition)
    {
        return array('synthetic' => true);
    }

    /**
     * Try to hydrate definition class from entity into definition array
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
     */
    private function hydrateDefinitionClass(Definition $definition, array &$definition_array)
    {
        if (null !== $definition->getClass()) {
            $definition_array['class'] = $definition->getClass();
        }
    }

    /**
     * Try to hydrate definition arguments from entity into definition array
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
     */
    private function hydrateDefinitionArguments(Definition $definition, array &$definition_array)
    {
        foreach ($definition->getArguments() as $arg) {
            $definition_array['arguments'][] = $this->convertArgument($arg);
        }
    }

    /**
     * Try to hydrate definition factory class from entity into definition array
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
     */
    private function hydrateDefinitionFactoryClass(Definition $definition, array &$definition_array)
    {
        if (null !== $definition->getFactoryClass()) {
            $definition_array['factory_class'] = $definition->getFactoryClass();
        }
    }

    /**
     * Try to hydrate definition factory service from entity into definition array
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
     */
    private function hydrateDefinitionFactoryService(Definition $definition, array &$definition_array)
    {
        if (null !== $definition->getFactoryService()) {
            $definition_array['factory_service'] = $definition->getFactoryService();
        }
    }

    /**
     * Try to hydrate definition factory method from entity into definition array
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
     */
    private function hydrateDefinitionFactoryMethod(Definition $definition, array &$definition_array)
    {
        if (null !== $definition->getFactoryMethod()) {
            $definition_array['factory_method'] = $definition->getFactoryMethod();
        }
    }

    /**
     * Converts object into string and returns it; if it's a string or a boolean, this method
     * won't do anything; it only converts Symfony\Component\DependencyInjection\Reference into
     * string
     *
     * @param  mixed          $argument the argument we may do conversion
     *
     * @return boolean|string the argument in acceptable type
     */
    private function convertArgument($argument)
    {
        if (is_object($argument) && is_a($argument, 'Symfony\Component\DependencyInjection\Reference')) {
            $argument = '@' . $argument->__toString();
        }

        return $argument;
    }

    /**
     * Hydrate definition tags from entity into definition array
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
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
     * Hydrate definition array method calls with definition entity
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
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

    /**
     * Try to hydrate definition array method calls with definition entity
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
     */
    private function hydrateDefinitionConfigurator(Definition $definition, array &$definition_array)
    {
        if (null !== $configurator = $definition->getConfigurator()) {
            if (true === is_string($configurator)) {
                $definition_array['configurator'] = $definition->getConfigurator();
            } else {
                $definition_array['configurator'] = array($this->convertArgument($configurator[0]), $configurator[1]);
            }
        }
    }

    /**
     * Try to hydrate definition array method calls with definition entity
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
     */
    private function hydrateDefinitionParent(Definition $definition, array &$definition_array)
    {
        if (true === ($definition instanceof DefinitionDecorator)) {
            $definition_array['parent'] = $definition->getParent();
            var_dump($definition_array); die;
        }
    }

    /**
     * Try to hydrate definition scope property from entity into definition array
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
     */
    private function hydrateDefinitionScopeProperty(Definition $definition, array &$definition_array)
    {
        if (ContainerInterface::SCOPE_CONTAINER !== $definition->getScope()) {
            $definition_array['scope'] = $definition->getScope();
        }
    }

    /**
     * Try to hydrate definition public property from entity into definition array
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
     */
    private function hydrateDefinitionPublicProperty(Definition $definition, array &$definition_array)
    {
        if (false === $definition->isPublic()) {
            $definition_array['public'] = false;
        }
    }

    /**
     * Try to hydrate definition abstract property from entity into definition array
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
     */
    private function hydrateDefinitionAbstractProperty(Definition $definition, array &$definition_array)
    {
        if (true === $definition->isAbstract()) {
            $definition_array['abstract'] = true;
        }
    }

    /**
     * Try to hydrate definition file property from entity into definition array
     *
     * @param  Definition $definition       the definition to convert
     * @param  array      $definition_array the definition array (passed by reference)
     */
    private function hydrateDefinitionFileProperty(Definition $definition, array &$definition_array)
    {
        if (null !== $definition->getFile()) {
            $definition_array['file'] = $definition->getFile();
        }
    }

    /**
     * Looking for every services with `dumpable` tag and calls DumpableServiceInterface::dump()
     * Only loaded services are dumped
     *
     * @param  array  $options
     *
     * @return array contains the dump of every dumpable services indexed by the service id
     *
     * @throws ServiceNotDumpableException raises if you declare a service as dumpable but it did not
     *                                     implement DumpableServiceInterface
     */
    private function dumpDumpableServices(array $options)
    {
        $services_dump = array();
        foreach ($this->container->findTaggedServiceIds('dumpable') as $service_id => $data) {
            if (false === $this->container->isLoaded($service_id)) {
                continue;
            }

            $service = $this->container->get($service_id);
            if ($service instanceof DumpableServiceInterface) {
                $services_dump[$service_id] = array(
                    'dump'        => $service->dump(),
                    'class_proxy' => $service->getClassProxy()
                );
            } else {
                throw new ServiceNotDumpableException(
                    $service_id,
                    true === isset($data['class']) ? $data['class'] : null
                );
            }
        }

        return $services_dump;
    }
}
