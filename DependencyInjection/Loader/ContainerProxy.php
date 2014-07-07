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

use BackBuilder\DependencyInjection\Container;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
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
     * [$raw_definitions description]
     * @var [type]
     */
    private $raw_definitions;

    /**
     * [$raw_definitions_id description]
     * @var [type]
     */
    private $raw_definitions_id;

    /**
     * [__construct description]
     */
    public function __construct(array $container_dump)
    {
        parent::__construct();

        $this->raw_definitions = $container_dump['services'];
        $this->getParameterBag()->add($container_dump['parameters']);
        $this->raw_definitions_id = array_keys($this->raw_definitions);
        $this->addAliases($container_dump['aliases']);
    }

    /**
     * [get description]
     * @param  [type] $id               [description]
     * @param  [type] $invalid_behavior [description]
     * @return [type]                   [description]
     */
    public function get($id, $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        $this->tryLoadDefinitionFromRaw($id);

        return parent::get($id, $invalid_behavior);
    }

    /**
     * [has description]
     * @param  [type]  $id [description]
     * @return boolean     [description]
     */
    public function has($id)
    {
        $this->tryLoadDefinitionFromRaw($id);

        return parent::has($id);
    }

    /**
     * [getDefinition description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getDefinition($id)
    {
        $this->tryLoadDefinitionFromRaw($id);

        return parent::getDefinition($id);
    }

    /**
     * [hasDefinition description]
     * @param  [type]  $id [description]
     * @return boolean     [description]
     */
    public function hasDefinition($id)
    {
        $this->tryLoadDefinitionFromRaw($id);

        return parent::hasDefinition($id);
    }

    /**
     * [getDefinitions description]
     * @return [type] [description]
     */
    public function getDefinitions()
    {
        $this->loadRawDefinitions();

        return parent::getDefinitions();
    }

    private function tryLoadDefinitionFromRaw($id)
    {
        if (true === is_string($id) && true === in_array($id, $this->raw_definitions_id)) {
            $this->setDefinition($id, $this->buildDefinition($this->raw_definitions[$id]));
            $this->raw_definitions_id = array_flip($this->raw_definitions_id);
            unset($this->raw_definitions_id[$id]);
            $this->raw_definitions_id = array_flip($this->raw_definitions_id);
        }
    }

    private function loadRawDefinitions()
    {
        foreach ($this->raw_definitions_id as $id) {
            $this->tryLoadDefinitionFromRaw($id);
        }
    }

    /**
     * [buildDefinition description]
     * @param  array  $definition_array [description]
     * @return [type]                   [description]
     */
    private function buildDefinition(array $array)
    {
        $definition = new Definition();
        if (true === array_key_exists('synthetic', $array)) {
            $definition->setSynthetic($array['synthetic']);
        }

        $this->setDefinitionClass($definition, $array);
        $this->setDefinitionArguments($definition, $array);
        $this->setDefinitionTags($definition, $array);
        $this->setDefinitionMethodCalls($definition, $array);

        return $definition;
    }

    /**
     * [setDefinitionClass description]
     * @param Definition $definition [description]
     * @param array      $array      [description]
     */
    private function setDefinitionClass(Definition $definition, array $array)
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
    private function setDefinitionArguments(Definition $definition, array $array)
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
    private function convertArgument($argument)
    {
        if (true === is_string($argument) && '@' === $argument[0]) {
            $argument = new Reference(substr($argument, 1));
        }

        return $argument;
    }

    /**
     * [setDefinitionTags description]
     * @param Definition $definition [description]
     * @param array      $array      [description]
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
     * [setDefinitionMethodCalls description]
     * @param Definition $definition [description]
     * @param array      $array      [description]
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
}