<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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

namespace BackBee\Validator;

use Doctrine\ORM\EntityManager;

/**
 * Entity's validator.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class EntityValidator extends AValidator
{
    const CONFIG_PARAMETER_ENTITY = 'entity';
    const PASSWORD_VALIDATOR = 'password';
    const UNIQUE_VALIDATOR = 'unique';
    const PREFIX_PASSWORD_CONFIRM = 'conf-';

    protected $em;

    /**
     * Form validator constructor.
     *
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Validate all datas with config.
     *
     * @param object $entity
     * @param array  $datas
     * @param array  $errors
     * @param array  $config
     * @param string $prefix
     *
     * @return object
     */
    public function validate($entity, array $datas = array(), array &$errors = array(), array $config = array(), $prefix = '')
    {
        if (false === $this->isValid($entity, $config)) {
            return $entity;
        }
        if (false === empty($prefix)) {
            $datas = $this->deleteElementWhenPrefix($datas, $prefix);
        }

        foreach ($datas as $key => $data) {
            if (true === isset($config[$key])) {
                $cConfig = $config[$key];

                $do_treatment = true;
                if (true === isset($cConfig[self::CONFIG_PARAMETER_MANDATORY]) &&
                    false === $cConfig[self::CONFIG_PARAMETER_MANDATORY] &&
                    true === empty($data)) {
                    $do_treatment = false;
                }

                if (true === $do_treatment) {
                    if (true === isset($cConfig[self::CONFIG_PARAMETER_VALIDATOR])) {
                        foreach ($cConfig[self::CONFIG_PARAMETER_VALIDATOR] as $validator => $validator_conf) {
                            if (self::UNIQUE_VALIDATOR === $validator) {
                                $this->doUniqueValidator($entity, $errors, $key, $data, $validator_conf);
                            } elseif (self::PASSWORD_VALIDATOR === $validator) {
                                $this->doPasswordValidator($errors, $key, $data, $datas, $validator_conf);
                            } else {
                                $this->doGeneralValidator($data, $key, $validator, $validator_conf, $errors);
                            }
                        }
                    }

                    if (false === empty($prefix)) {
                        $key = str_replace($prefix, '', $key);
                    }
                    if (true === method_exists($entity, 'set'.ucfirst($key))) {
                        $do_set = true;
                        if (true === isset($cConfig[self::CONFIG_PARAMETER_SET_EMPTY])) {
                            if (false === $cConfig[self::CONFIG_PARAMETER_SET_EMPTY] && true === empty($data)) {
                                $do_set = false;
                            }
                        }
                        if (true === $do_set) {
                            if (true === isset($cConfig[self::CONFIG_PARAMETER_ENTITY])) {
                                $data = $this->em->find($cConfig[self::CONFIG_PARAMETER_ENTITY], $data);
                            }
                            $entity->{'set'.ucfirst($key)}($data);
                        }
                    }
                }
            }
        }

        return $entity;
    }

    /**
     * Valid if this field is unique.
     *
     * @param array  $errors
     * @param string $key
     * @param string $data
     * @param array  $config
     */
    public function doUniqueValidator($entity, &$errors, $key, $data, $config)
    {
        if (false === empty($data)) {
            $entities_found = $this->em->getRepository(get_class($entity))->findBy(array($key => $data));
            if (false === empty($entities_found)) {
                foreach ($entities_found as $entity_found) {
                    $check = 0;
                    foreach ($this->getIdProperties($entity_found) as $property) {
                        $method = 'get'.ucfirst($property);
                        if ($entity->{$method}() !== $entity_found->{$method}()) {
                            $check++;
                        }
                    }
                    if ($check == count($this->getIdProperties($entity))) {
                        $errors[$key] = $config[self::CONFIG_PARAMETER_ERROR];
                        break;
                    }
                }
            }
        }
    }

    /**
     * Valid a password with confirmation.
     *
     * @param array  $errors
     * @param string $key
     * @param array  $data
     * @param array  $datas
     * @param array  $config
     */
    public function doPasswordValidator(&$errors, $key, $data, $datas, $config)
    {
        if ($data !== $datas[self::PREFIX_PASSWORD_CONFIRM.$key]) {
            $errors[$key] = $config[self::CONFIG_PARAMETER_ERROR];
        }
    }

    /**
     * Verify if datas is valid.
     *
     * @param object $entity
     * @param array  $config
     *
     * @return boolean
     */
    public function isValid($entity, $config)
    {
        if (false === is_object($entity)) {
            return false;
        }

        if (true === empty($config)) {
            return false;
        }

        return true;
    }

    /**
     * @param object $entity
     *
     * @return \ReflectionClass
     */
    public function getReflectionClass($entity)
    {
        if (null === $entity || false === is_object($entity)) {
            throw new \InvalidArgumentException(sprintf('Entity must be an object'));
        }

        return new \ReflectionClass(get_class($entity));
    }

    /**
     * Get id of object.
     *
     * @param object $entity
     *
     * @return array
     */
    public function getIdProperties($entity)
    {
        $ids = array();
        $reflection_class = $this->getReflectionClass($entity);
        foreach ($reflection_class->getProperties() as $property) {
            if (false !== strpos($property->getDocComment(), '@Id')) {
                $ids[] = $property->getName();
            }
        }

        return $ids;
    }
}
