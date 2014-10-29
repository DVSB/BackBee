<?php
namespace BackBuilder\Rest\Patcher;

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

use Metadata\MetadataFactoryInterface;

/**
 * RightManager is able to build a mapping of authorized action on entity's properties with
 * the provided Metadata\MetadataFactoryInterface
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class RightManager
{
    /**
     * This factory will be used to build authorization mapping
     *
     * @var \Metadata\MetadataFactoryInterface
     */
    private $metadata_factory;

    /**
     * mapping of entity namespace and properties authorized actions
     *
     * @var array
     */
    private $rights;

    /**
     * RightManager's constructor
     *
     * @param MetadataFactoryInterface $metadata_factory the factory to use to build authorization mapping
     */
    public function __construct(MetadataFactoryInterface $metadata_factory)
    {
        $this->metadata_factory = $metadata_factory;
        $this->rights = array();
    }

    /**
     * Return true if the $operation is authorized on $entity's $attribute, else false
     *
     * @param  object $entity
     * @param  string $attribute
     * @param  string $operation
     *
     * @return boolean true if $operation is authorized, else false
     */
    public function authorized($entity, $attribute, $operation)
    {
        $authorized = true;
        $classname = get_class($entity);
        if (false === array_key_exists($classname, $this->rights)) {
            $this->buildRights($classname);
        }

        $attribute = str_replace('/', '', $attribute);
        if (true === $authorized && false === array_key_exists($attribute, $this->rights[$classname])) {
            $authorized = false;
        }

        if (true === $authorized && false === in_array($operation, $this->rights[$classname][$attribute])) {
            $authorized = false;
        }

        return $authorized;
    }

    /**
     * Builds the authtorization mapping for the given $classname
     *
     * @param  string $classname
     */
    private function buildRights($classname)
    {
        $metadatas = $this->metadata_factory->getMetadataForClass($classname);
        $reflection = new \ReflectionClass($classname);

        $this->rights[$classname] = array();
        if (null !== $metadatas) {
            foreach ($metadatas->propertyMetadata as $property_name => $property_metadata) {
                $property_name = $this->cleanPropertyName($property_name);
                $this->rights[$classname][$property_name] = array();

                if (
                    false === $property_metadata->readOnly
                    && $reflection->hasMethod($this->buildProperMethodName('set', $property_name))
                ) {
                    $this->rights[$classname][$property_name][] = 'replace';
                }
            }
        }
    }

    /**
     * This method will replace '_' by '' of $property_name if its first letter is an underscore (_)
     *
     * @param  string $property_name the property we want to clean
     *
     * @return string cleaned property name
     */
    private function cleanPropertyName($property_name)
    {
        return preg_replace('#^_([\w_]+)#', '$1', $property_name);
    }

    /**
     * Builds a valid method name for property name; Replaces every '_' by ''
     * and apply ucfirst to every words seperated by an underscore
     *
     * @param  string $prefix        the prefix to prepend to the method name (example: 'get', 'set', 'is')
     * @param  string $property_name
     *
     * @return string a valid method name
     */
    private function buildProperMethodName($prefix, $property_name)
    {
        $method_name = explode('_', $property_name);
        $method_name = array_map(function ($str) {
            return ucfirst($str);
        }, $method_name);

        return $prefix . implode('', $method_name);
    }
}
