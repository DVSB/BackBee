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

namespace BackBuilder\Importer;

use BackBuilder\BBApplication,
    BackBuilder\Config\Config;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Importer
{
    private $_application;
    private $_config;
    private $_connector;
    private $_converter;
    private $_import_config;
    private $_ids;
    private $_object_identifier;

    /**
     * Class constructor
     *
     * @param \BackBuilder\BBApplication $application
     * @param \BackBuilder\Importer\IImporterConnector $connector
     * @param \BackBuilder\Config\Config $config
     */
    public function __construct(BBApplication $application, IImporterConnector $connector, Config $config)
    {
        $this->_application = $application;
        $this->_connector = $connector;
        $this->_config = $config;
    }

    /**
     * @param type $flush_every if you don't need flush put 0
     * @param boolean $check_for_existing
     * @return boolean
     */
    public function run($class, $config, $flush_every, $check_for_existing)
    {
        $start_time = microtime(true);
        $this->setConverter($this->initConvertion($config));
        $values = $this->getConverter()->getRows($this);
        \BackBuilder\Util\Buffer::dump('Importation of ' . count($values) . ' ' . $class . ' was started' . "\n");
        $i = 0;
        $entities = array();
        if (count($values) == 0) {return;}
        foreach ($values as $value) {
            //if (false === $check_for_existing || (array_key_exists($this->_object_identifier, (array)$value) && !in_array(md5($value[$this->_object_identifier]), $this->_ids))) {
                $entities[] = $this->getConverter()->convert($value);
            //}
            if (++$i === $flush_every) {
                $this->save($entities, $check_for_existing);
                $i = 0;
                unset($entities);
                $entities = array();
            }
        }
        if ($flush_every != 0) {
            $this->save($entities, $check_for_existing);
        }
        unset($entities);
        $this->getConverter()->onImportationFinish();
        \BackBuilder\Util\Buffer::dump(count($values) . ' ' . $class . ' imported in ' . (microtime(true) - $start_time) . ' s' . "\n");
    }

    /**
     *
     * @param array $config
     * @return \BackBuilder\Importer\IConverter
     */
    final protected function initConvertion(array $config)
    {
        $this->_import_config = $config;
        $converter = new $config['converter_class']($this);
        $converter->setBBEntity($config['entity_class']);
        $converter->beforeImport($this, $config);
        $em = $this->_application->getEntityManager();
        
        $table_name = $em->getClassMetadata($config['entity_class'])->getTableName();
        $where_clause = array_key_exists('where_clause', $config) ? $config['where_clause'] : 1;
        $id_label = array_key_exists('id_label', $config) ? $config['id_label'] : 'uid';
        $this->_object_identifier = array_key_exists('object_identifier', $config) ? $config['object_identifier'] : 'id';
        $this->_ids = $this->getExistingIds($id_label, $table_name, $where_clause);

        return $converter;
    }

    final protected function getExistingIds($id_label, $table_name, $where_clause)
    {
        $sql= 'SELECT ' . $id_label . ' FROM ' . $table_name . ' WHERE ' . $where_clause;
        $statement = $this->_application->getEntityManager()->getConnection()->executeQuery($sql);
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function newBBEntity($classname, $uid)
    {
        if (!in_array($uid, $this->_ids)) {
            return new $classname($uid);
        } else {
            $repo = $this->_application->getEntityManager()->getRepository($classname);
            return $repo->find($uid);
        }
    }

    /**
     * Return an existing class content or a new one if unfound
     * @param string $classname
     * @param string $uid
     * @return \BaclBuilder\ClassContent\AClassContent
     */
    public function getBBEntity($classname, $uid)
    {
        $em = $this->_application->getEntityManager();
        if (null === $entity = $em->find($classname, $uid)) {
            $entity = new $classname($uid);
            $em->persist($entity);
        }

        return $entity;
    }

    /**
     * Save Entities
     *
     * @param array $entities
     */
    public function save(array $entities, $check_for_existing = true)
    {
        $id_label = 'get' . ucfirst(array_key_exists('id_label', $this->_import_config) ? $this->_import_config['id_label'] : 'uid');

        \BackBuilder\Util\Buffer::dump('Saving...' . "\n");$i= 0;
        foreach ($entities as $entity) {
//            if (true === $check_for_existing && !in_array($entity->{$id_label}(), $this->_ids)) {
//                $this->_application->getEntityManager()->persist($entity);
//            }
            $this->_application->getEntityManager()->persist($entity);
        }
        $this->_application->getEntityManager()->flush();
        $this->getConverter()->afterEntitiesFlush($this, $entities);
        $this->flushMemory();
    }

    public function flushMemory()
    {
        $this->_application->getEntityManager()->clear();
        gc_collect_cycles();
        $this->getConverter()->beforeImport($this, $this->_import_config);
    }

    /**
     * @return /BackBuilder/BBApplication
     */
    final public function getApplication()
    {
        return $this->_application;
    }

    final public function find($string)
    {
        return $this->_connector->find($string);
    }

    final protected function _loadRelations()
    {
       return $this->getConfig()->getSection('relations');
    }

    final protected function getConfig()
    {
        return $this->_config;
    }

    final protected function getConverter()
    {
        return $this->_converter;
    }

    final protected function setConverter($converter)
    {
        return $this->_converter = $converter;
    }
}