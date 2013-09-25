<?php
namespace BackBuilder\Importer;

use BackBuilder\BBApplication,
    BackBuilder\Config\Config;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @copyright   Lp system
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
//        if (array_key_exists('php_config', $config)) {
//            $maxExecutionTime = (array_key_exists('max_execution_time', $config['php_config'])) ? (int)$config['php_config']['max_execution_time'] : (int)ini_get('max_execution_time');
//            $memoryLimit = (array_key_exists('max_execution_time', $config['php_config'])) ? $config['php_config']['max_execution_time'] : ini_get('max_execution_time');
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '2048M');
//        }
    }

    /**
     * @param type $flush_every if you don't need flush put 0
     * @param boolean $check_for_existing 
     * @return boolean
     */
    public function run($flush_every = 1000, $check_for_existing = true)
    {
        $relations = $this->_loadRelataions();
        if (0 == count($relations)) return false;
        foreach ($relations as $class => $config) {
            $start_time = microtime(true);
            $this->setConverter($this->initConvertion($config));
            $values = $this->getConverter()->getRows($this);
            \BackBuilder\Util\Buffer::dump('Importation of ' . count($values) . ' ' . $class . ' was started' . "\n");
            $i = 0;
            $entities = array();
            if (count($values) == 0) return;
            foreach ($values as $value) {
                if (false === $check_for_existing || (array_key_exists($this->_object_identifier, (array)$value) && !in_array(md5($value[$this->_object_identifier]), $this->_ids))) {
                    try {
                        $entities[] = $this->getConverter()->convert($value);
                    } catch (\Exception $error) {
                        \BackBuilder\Util\Buffer::dump($error->getMessage() . "\n");
                        $this->_application->getContainer()->removeDefinition('em');
                        $this->_application->getEntityManager();
                    }
                }
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
            \BackBuilder\Util\Buffer::dump(count($values) . ' ' . $class . ' imported in ' . (microtime(true) - $start_time) . ' s' . "\n");
        }
        return true;
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

        \BackBuilder\Util\Buffer::dump('Saving...' . "\n");
        foreach ($entities as $entity) {
            if (true === $check_for_existing && !in_array($entity->{$id_label}(), $this->_ids)) {
                $this->_application->getEntityManager()->persist($entity);
            }
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
     * @codeCoverageIgnore
     * @return /BackBuilder/BBApplication
     */
    final public function getApplication()
    {
        return $this->_application;
    }

    /**
     * @codeCoverageIgnore
     * @param type $string
     * @return array
     */
    final public function find($string)
    {
        return $this->_connector->find($string);
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    final protected function _loadRelataions()
    {
       return $this->getConfig()->getSection('relations');
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    final protected function getConfig()
    {
        return $this->_config;
    }
    
    /**
     * @codeCoverageIgnore
     * @return IConverter
     */
    final protected function getConverter()
    {
        return $this->_converter;
    }
    
    /**
     * return
     * @param IConverter $converter
     * @return \BackBuilder\Importer\Importer
     */
    final protected function setConverter($converter)
    {
        $this->_converter = $converter;
        return $this;
    }
}