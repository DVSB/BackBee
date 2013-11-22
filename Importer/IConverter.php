<?php
namespace BackBuilder\Importer;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @copyright   Lp system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
interface IConverter
{
    /**
     * Returns the values
     *
     * @return array
     */
    public function getRows(Importer $importer);

    /**
     * Convert each entries of the array into a BackBuilder Object Entity
     *
     * @param array $values
     * @return array composed by BackBuilder Object Entity
     */
    public function convert($values);

    /**
     * Function executed before the import started
     *
     * @param \BackBuilder\Importer\Importer $importer
     * @param array $config
     */
    public function beforeImport(Importer $importer, array $config);

    /**
     * Function executed after each entity fush
     *
     * @param \BackBuilder\Importer\Importer $importer
     * @param array $entities
     */
    public function afterEntitiesFlush(Importer $importer, array $entities);

    /**
     * Returns an existing or new object of BB Entity according to $identifier
     * @param  string $identifier 
     * @return BackBuilder\ClassContent\AClassContent $entity
     */
    public function getBBEntity($identifier);

    /**
     * Returns in an array list of every existing uid
     * @return array 
     */
    public function getAvailableKeys();
}