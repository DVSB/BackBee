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

namespace BackBuilder\DependencyInjection;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ContainerProxy extends Container
{
    /**
     * raw definitions provided by container dump array (key: services)
     *
     * @var array
     */
    private $raw_definitions;

    /**
     * raw definitions id provided by container dump array (keys of container dump array services)
     *
     * @var array
     */
    private $raw_definitions_id;

    /**
     * shows if the container has been compiled before being dump or not
     *
     * @var boolean
     */
    private $already_compiled;

    /**
     * ContainerProxy's constructor;
     *
     * @param array $container_dump the container dump from where we can restore entirely the container
     */
    public function init(array $container_dump = array())
    {
        if (0 < count($container_dump)) {
            $this->raw_definitions = $container_dump['services'];

            if (true === isset($container_dump['parameters'])) {
                $this->getParameterBag()->add($container_dump['parameters']);
            }

            if (true === isset($container_dump['aliases'])) {
                $this->addAliases($container_dump['aliases']);
            }

            $this->already_compiled = $container_dump['is_compiled'];
        } elseif (true === $this->hasParameter('services_dump') && true === $this->hasParameter('is_compiled')) {
            $this->raw_definitions = unserialize($this->getParameter('services_dump'));
            $this->getParameterBag()->remove('services_dump');
            $this->already_compiled = $this->getParameter('is_compiled');
            $this->getParameterBag()->remove('is_compiled');
        } else {
            throw new \InvalidArgumentException(
                'Unable to find services definitions in provided parameters or in current container'
            );
        }

        $this->raw_definitions_id = array_keys($this->raw_definitions);
        $this->is_restored = true;
    }

    /**
     * Returns boolean that determine if container has been compiled before the dump or not
     *
     * @return boolean true if the container has been compiled before the dump, otherwise false
     */
    public function isCompiled()
    {
        return $this->already_compiled;
    }

    /**
     * @see Symfony\Component\DependencyInjection\ContainerBuilder::set
     */
    public function set($id, $service, $scope = self::SCOPE_CONTAINER)
    {
        $definition = true === $this->hasDefinition($id) ? $this->getDefinition($id) : null;
        if (null !== $definition && true === $definition->isSynthetic()) {
            foreach ($definition->getMethodCalls() as $method) {
                $arguments = array();
                foreach ($method[1] as $argument) {
                    if (true === is_object($argument) && true === ($argument instanceof Reference)) {
                        if (false === $this->has($argument->__toString())) {
                            continue;
                        }

                        $argument = $this->get($argument->__toString());
                    }

                    $arguments[] = $argument;
                }

                call_user_func_array(array($service, $method[0]), $arguments);
            }
        }

        parent::set($id, $service, $scope);
    }

    /**
     * @see Symfony\Component\DependencyInjection\ContainerBuilder::has
     */
    public function has($id)
    {
        $this->tryLoadDefinitionFromRaw($id);

        return parent::has($id);
    }

    /**
     * @see Symfony\Component\DependencyInjection\ContainerBuilder::getDefinition
     */
    public function getDefinition($id)
    {
        $this->tryLoadDefinitionFromRaw($id);

        return parent::getDefinition($id);
    }

    /**
     * @see Symfony\Component\DependencyInjection\ContainerBuilder::hasDefinition
     */
    public function hasDefinition($id)
    {
        $this->tryLoadDefinitionFromRaw($id);

        return parent::hasDefinition($id);
    }

    /**
     * @see Symfony\Component\DependencyInjection\ContainerBuilder::getDefinitions
     */
    public function getDefinitions()
    {
        $this->loadRawDefinitions();

        return parent::getDefinitions();
    }

    /**
     * Try to load definition by looking for its raw definition with the provided $id
     *
     * @param string $id the id of the service we try to load its definition
     *
     * @return null|Symfony\Component\DependencyInjection\Definition return null if no definition has been found
     *                                                               in raw definitions, else the Definition object newly build
     */
    private function tryLoadDefinitionFromRaw($id)
    {
        $definition = null;
        if (is_string($id) && is_array($this->raw_definitions_id) && in_array($id, $this->raw_definitions_id)) {
            $this->setDefinition($id, $definition = $this->buildDefinition($this->raw_definitions[$id]));
            $this->raw_definitions_id = array_flip($this->raw_definitions_id);
            unset($this->raw_definitions_id[$id]);
            $this->raw_definitions_id = array_flip($this->raw_definitions_id);
        }

        return $definition;
    }

    /**
     * Load every raw definitions and convert them into definition object
     */
    private function loadRawDefinitions()
    {
        foreach ($this->raw_definitions_id as $id) {
            $this->tryLoadDefinitionFromRaw($id);
        }
    }

    /**
     * Build a definition from the definition's array provided as current method parameter
     *
     * @param array $array the raw definition's array
     *
     * @return Symfony\Component\DependencyInjection\Definition the definition object
     */
    private function buildDefinition(array $array)
    {
        $definition = null;
        if (true === array_key_exists('parent', $array)) {
            $definition = new DefinitionDecorator($array['parent']);
        } else {
            $definition = new Definition();
        }

        if (true === array_key_exists('synthetic', $array)) {
            $definition->setSynthetic($array['synthetic']);
        }

        $this->setDefinitionClass($definition, $array);
        $this->setDefinitionArguments($definition, $array);
        $this->setDefinitionFactoryClass($definition, $array);
        $this->setDefinitionFactoryService($definition, $array);
        $this->setDefinitionFactoryMethod($definition, $array);
        $this->setDefinitionTags($definition, $array);
        $this->setDefinitionMethodCalls($definition, $array);
        $this->setDefinitionProperties($definition, $array);
        $this->setDefinitionConfigurator($definition, $array);

        return $definition;
    }

    /**
     * Set the definition class into definition object if it exists
     *
     * @param Definition $definition definition object to hydrate
     * @param array      $array      raw definition datas
     */
    private function setDefinitionClass(Definition $definition, array $array)
    {
        if (true === array_key_exists('class', $array)) {
            $definition->setClass($array['class']);
        }
    }

    /**
     * Set the definition arguments into definition object if it exists
     *
     * @param Definition $definition definition object to hydrate
     * @param array      $array      raw definition datas
     */
    private function setDefinitionArguments(Definition $definition, array $array)
    {
        if (true === array_key_exists('arguments', $array)) {
            foreach ($array['arguments'] as $arg) {
                $definition->addArgument($this->convertArgument($arg));
            }
        }
    }

    /**
     * Set the definition factory class into definition object if it exists
     *
     * @param Definition $definition definition object to hydrate
     * @param array      $array      raw definition datas
     */
    private function setDefinitionFactoryClass(Definition $definition, array $array)
    {
        if (true === array_key_exists('factory_class', $array)) {
            $definition->setFactoryClass($array['factory_class']);
        }
    }

    /**
     * Set the definition factory service into definition object if it exists
     *
     * @param Definition $definition definition object to hydrate
     * @param array      $array      raw definition datas
     */
    private function setDefinitionFactoryService(Definition $definition, array $array)
    {
        if (true === array_key_exists('factory_service', $array)) {
            $definition->setFactoryService($array['factory_service']);
        }
    }

    /**
     * Set the definition factory method into definition object if it exists
     *
     * @param Definition $definition definition object to hydrate
     * @param array      $array      raw definition datas
     */
    private function setDefinitionFactoryMethod(Definition $definition, array $array)
    {
        if (true === array_key_exists('factory_method', $array)) {
            $definition->setFactoryMethod($array['factory_method']);
        }
    }

    /**
     * Converts a service string id into Reference if needed
     *
     * @param mixed $argument the argument we may convert
     *
     * @return mixed the argument which may be converted
     */
    private function convertArgument($argument)
    {
        if (true === is_string($argument) && 0 < strlen($argument) && '@' === $argument[0]) {
            $argument = new Reference(substr($argument, 1));
        }

        return $argument;
    }

    /**
     * Set the definition tags into definition object if it exists
     *
     * @param Definition $definition definition object to hydrate
     * @param array      $array      raw definition datas
     */
    private function setDefinitionTags(Definition $definition, array $array)
    {
        if (true === array_key_exists('tags', $array)) {
            if (false === is_array($array['tags'])) {
                throw new InvalidArgumentException(sprintf('Parameter "tags" must be an array for service "%s".', $id));
            }

            foreach ($array['tags'] as $tag) {
                if (!isset($tag['name'])) {
                    throw new InvalidArgumentException(sprintf(
                        'A "tags" entry is missing a "name" key for service "%s".',
                        $id
                    ));
                }

                $name = $tag['name'];
                unset($tag['name']);

                foreach ($tag as $attribute => $value) {
                    if (false === is_scalar($value)) {
                        throw new InvalidArgumentException(sprintf(
                            'A "tags" attribute must be of a scalar-type for service "%s", tag "%s".',
                            $id,
                            $name
                        ));
                    }
                }

                $definition->addTag($name, $tag);
            }
        }
    }

    /**
     * Set the definition method calls into definition object if it exists
     *
     * @param Definition $definition definition object to hydrate
     * @param array      $array      raw definition datas
     */
    private function setDefinitionMethodCalls(Definition $definition, array $array)
    {
        if (true === array_key_exists('calls', $array)) {
            foreach ($array['calls'] as $call) {
                $args = array();
                if (true === isset($call[1])) {
                    foreach ($call[1] as $arg) {
                        $args[] = $this->convertArgument($arg);
                    }
                }

                $definition->addMethodCall($call[0], $args);
            }
        }
    }

    /**
     * Set the definition configurator into definition object if it exists
     *
     * @param Definition $definition definition object to hydrate
     * @param array      $array      raw definition datas
     */
    private function setDefinitionConfigurator(Definition $definition, array $array)
    {
        if (true === array_key_exists('configurator', $array)) {
            $configurator = $array['configurator'];
            if (true === is_array($configurator)) {
                $configurator[0] = $this->convertArgument($configurator[0]);
            }

            $definition->setConfigurator($configurator);
        }
    }

    /**
     * Set the definition property (public/abstract/scope/file) into definition object if it exists
     *
     * @param Definition $definition definition object to hydrate
     * @param array      $array      raw definition datas
     */
    private function setDefinitionProperties(Definition $definition, array $array)
    {
        if (true === array_key_exists('public', $array)) {
            $definition->setPublic($array['public']);
        }

        if (true === array_key_exists('abstract', $array)) {
            $definition->setAbstract($array['abstract']);
        }

        if (true === array_key_exists('scope', $array)) {
            $definition->setScope($array['scope']);
        }

        if (true === array_key_exists('file', $array)) {
            $definition->setFile($array['file']);
        }
    }
}
