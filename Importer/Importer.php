<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\Importer;

use BackBee\BBApplication;
use BackBee\Config\Config;
use BackBee\Importer\Exception\ImporterException;
use BackBee\Util\Buffer;

/**
 * @category    BackBee
 * @package     BackBee\Importer
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
    private $_failedItemsCount = 0;

    /**
     * Class constructor
     *
     * @param \BackBee\BBApplication               $application
     * @param \BackBee\Importer\ImporterConnectorInterface $connector
     * @param \BackBee\Config\Config               $config
     */
    public function __construct(BBApplication $application, ImporterConnectorInterface $connector, Config $config)
    {
        $this->_application = $application;
        $this->_connector = $connector;
        $this->_config = $config;

        $this->_application->getEntityManager()->getConnection()->getConfiguration()->setSQLLogger(null);
    }

    /**
     * @param  type    $flush_every    if you don't need flush put 0
     * @param  boolean $check_existing
     * @return boolean
     */
    public function run($class, $config, $flush_every, $check_existing)
    {
        $starttime = microtime(true);

        $this->setConverter($this->initConvertion($config));
        $rows = $this->getConverter()->getRows($this);

        if (!is_array($rows) && !($rows instanceof \Countable)) {
            throw new ImporterException('Result set must be an array or Countable');
        }

        $items_count = count($rows);
        $limit = true === isset($config['limit']) ? $config['limit'] : null;
        if (0 === $items_count) {
            Buffer::dump(
                '=== Importation of '.$items_count.' can\'t be done, there is no item to convert.'."\n"
            );

            return;
        }

        Buffer::dump(
            "\n".'===== Importation of '.(null !== $limit ? $limit." (total: $items_count)" : $items_count)
            .' '.$class.' was started.'."\n\n"
        );

        $this->_doImport($rows, $flush_every, $check_existing, $limit);
        $rows = null;

        $this->getConverter()->onImportationFinish();
        $this->_converter = null;
        gc_collect_cycles();

        Buffer::dump(
            "\n".$this->_importedItemsCount.' '.$class.' imported in '
            .(microtime(true) - $starttime).' s ====='."\n\n"
        );

        Buffer::dump(
            "\n".$this->_failedItemsCount.' '.$class.' failed items ====='."\n\n", 'bold_red');
    }

    /**
     *
     * @param  array|\Traversable $rows
     * @param  int                $flush_every
     * @param  bool               $check_existing
     * @param  int|null           $limit
     * @throws ImporterException
     */
    private function _doImport($rows, $flush_every, $check_existing, $limit = null)
    {
        if (!is_array($rows) && !($rows instanceof \Traversable)) {
            throw new ImporterException('Result set must be an array or Traversable');
        }

        $i = 0;
        $count_null = 0;
        $total_ignored = 0;
        $entities = array();

        foreach ($rows as $row) {
            try {
                $entity = $this->getConverter()->convert($row);
            } catch (\Exception $e) {
                $row = null;
                Buffer::dump(
                    "===== Exception while processing row: ".$e->getMessage()."\n", 'bold_red');
                $this->_failedItemsCount++;
                continue;
            }
            if (null !== $entity) {
                $entities[] = $entity;

                if (++$i === $flush_every) {
                    $this->save($entities, $check_existing);
                    $i = 0;
                    $entities = null;
                    $entities = array();
                }

                $count_null = 0;
                $entity = null;
            } else {
                $count_null++;
                if (self::FLUSH_MEMORY_ON_NULL_EVERY <= $count_null) {
                    $total_ignored += $count_null;
                    Buffer::dump(
                        'Cleaning memory on null (every '.self::FLUSH_MEMORY_ON_NULL_EVERY
                        .' - total: '.$total_ignored.') : [BEFORE] '
                        .self::convertMemorySize(memory_get_usage())
                    );

                    if (0 < count($entities)) {
                        $this->save($entities, $check_existing);
                        $i = 0;
                        $entities = null;
                        $entities = array();
                    } else {
                        $this->flushMemory();
                    }

                    Buffer::dump('; [AFTER] '.self::convertMemorySize(memory_get_usage())."\n");
                    $count_null = 0;
                }
            }

            $row = null;

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

        $entities = null;
        $rows = null;
    }

    /**
     *
     * @param  array                        $config
     * @return \BackBee\Importer\ConverterInterface
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

        return $converter;
    }

    final protected function getExistingIds($id_label, $table_name, $where_clause)
    {
        $sql = 'SELECT :id_label FROM :table_name WHERE :where_clause';
        $statement = $this->_application->getEntityManager()->getConnection()->executeQuery($sql, array('id_label' => $id_label,
            'table_name' => $table_name,
            'where_clause' => $where_clause,
            )
        );

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
     * @param  string                                  $classname
     * @param  string                                  $uid
     * @return \BaclBuilder\ClassContent\AbstractClassContent
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
        $id_label = 'get'.ucfirst(array_key_exists('id_label', $this->_import_config) ? $this->_import_config['id_label'] : 'uid');

        $starttime = microtime(true);
        Buffer::dump('Saving '.count($entities).' items...');

        foreach ($entities as $entity) {
            if (null !== $entity && false === $this->_application->getEntityManager()->contains($entity)) {
                $this->_application->getEntityManager()->persist($entity);
            }

            $this->_importedItemsCount++;
        }

        $this->_application->getEntityManager()->flush();
        $this->getConverter()->afterEntitiesFlush($this, $entities);
        $this->flushMemory();

        Buffer::dump(' in '.(microtime(true) - $starttime).' s (total: '
            .$this->_importedItemsCount.' - memory status: '.self::convertMemorySize(memory_get_usage()).")\n");
    }

    public static function convertMemorySize($size)
    {
        $unit = array('b','kb','mb','gb','tb','pb');

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '.$unit[$i];
    }

    public function flushMemory()
    {
        $this->_application->getEntityManager()->clear();
        gc_collect_cycles();

        $this->getConverter()->beforeImport($this, $this->_import_config);
    }

    /**
     * @return /BackBee/BBApplication
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

    /**
     *
     * @return \BackBee\Importer\ImporterConnectorInterface
     */
    public function getConnector()
    {
        return $this->_connector;
    }
}
