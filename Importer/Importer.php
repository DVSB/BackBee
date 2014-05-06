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
    BackBuilder\Config\Config,
    BackBuilder\Util\Buffer;

use Doctrine\DBAL\Driver\PDOStatement;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>, n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Importer
{
    const FLUSH_MEMORY_ON_NULL_EVERY = 1000;

    private $_application;
    private $_config;
    private $_connector;
    private $_converter;
    private $_import_config;
    private $_ids;
    private $_object_identifier;
    private $_importedItemsCount = 0;

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
     * @param boolean $check_existing
     * @return boolean
     */
    public function run($class, $config, $flush_every, $check_existing)
    {
        $starttime = microtime(true);

        $this->setConverter($this->initConvertion($config));
        $statement = $this->getConverter()->getRows($this);
        $items_count = $statement->rowCount();
        $limit = true === isset($config['limit']) ? $config['limit'] : null;
        if (0 === $items_count) {
            Buffer::dump(
                '=== Importation of ' . $items_count . ' can\'t be done, there is no item to convert.' . "\n"
            );

            return;
        }

        Buffer::dump(
            "\n" . '===== Importation of ' . (null !== $limit ? $limit . " (total: $items_count)" : $items_count) 
            . ' ' . $class . ' was started.' . "\n\n"
        );

        $this->_doImport($statement, $flush_every, $check_existing, $limit);
        unset($statement);

        $this->getConverter()->onImportationFinish();
        unset($this->_converter);
        gc_collect_cycles();

        Buffer::dump(
            "\n" . $this->_importedItemsCount . ' ' . $class . ' imported in ' 
            . (microtime(true) - $starttime) . ' s =====' . "\n\n"
        );
    }

    private function _doImport(PDOStatement $statement, $flush_every, $check_existing, $limit = null)
    {
        $i = 0;
        $count_null = 0;
        $entities = array();

        while ($row = $statement->fetch()) {
            $entity = $this->getConverter()->convert($row);

            if (null !== $entity) {
                $entities[] = $entity;

                if (++$i === $flush_every) {
                    $this->save($entities, $check_existing);
                    $i = 0;
                    unset($entities);
                    $entities = array();
                }
                
                $count_null = 0;
                unset($entity);
            } else {
                $count_null++;
                if (self::FLUSH_MEMORY_ON_NULL_EVERY <= $count_null) {
                    Buffer::dump('Before cleaning memory on null: ' .  self::convertMemorySize(memory_get_usage()) . "\n");
                    
                    if (0 < count($entities)) {
                        $this->save($entities, $check_existing);
                        $i = 0;
                        unset($entities);
                        $entities = array();
                    } else {
                        $this->flushMemory();                        
                    }

                    Buffer::dump('After cleaning memory on null: ' . self::convertMemorySize(memory_get_usage()) . "\n");
                    $count_null = 0;
                }
            }

            unset($row);
            
            if (null !== $limit) {
                $limit--;
                if (0 === $limit) {
                    break;
                }
            }
        }

        if ($flush_every > $i && 0 < count($entities)) {
            $this->save($entities, $check_existing);
        } else {
            $this->flushMemory();
        }

        unset($entities);
        unset($statement);
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
    public function save(array $entities, $check_existing = true)
    { 
        $id_label = 'get' . ucfirst(array_key_exists('id_label', $this->_import_config) ? $this->_import_config['id_label'] : 'uid');

        $starttime = microtime(true);
        Buffer::dump('Saving ' . count($entities) . ' items...');

        foreach ($entities as $entity) {
            //if (true === $check_existing && !in_array($entity->{$id_label}(), $this->_ids)) {
            //  $this->_application->getEntityManager()->persist($entity);
            //}
            if (null !== $entity && false === $this->_application->getEntityManager()->contains($entity)) {
                $this->_application->getEntityManager()->persist($entity);
            }

            $this->_importedItemsCount++;
        }

        $this->_application->getEntityManager()->flush();

        $this->getConverter()->afterEntitiesFlush($this, $entities);
        $this->flushMemory();

        Buffer::dump(' in ' . (microtime(true) - $starttime) . ' s (total: ' 
            . $this->_importedItemsCount . ' - memory status: ' . self::convertMemorySize(memory_get_usage()) . ")\n");
    }

    public static function convertMemorySize($size)
    {
        $unit = array('b','kb','mb','gb','tb','pb');

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
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