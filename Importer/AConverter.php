<?php
namespace BackBuilder\Importer;

use BackBuilder\ClassContent\AClassContent,
    BackBuilder\Util\String;

/**
 * @category    BackBuilder
 * @package     BackBuilder\BddExport
 * @copyright   Lp system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
abstract class AConverter implements IConverter
{
    /**
     * @var Object
     */
    protected $_bb_entity;

    /**
     * The current importer running
     * @var \BackBuilder\Importer
     */
    protected $_importer;

    /**
     * Class constructor
     * 
     * @codeCoverageIgnore
     * @param \BackBuilder\Importer\Importer $importer
     */
    public function __construct(Importer $importer = null) {
        $this->_importer = $importer;
    }
    
    /**
     * return the BackBuilder Entity Object
     * 
     * @codeCoverageIgnore
     * @return Object
     */
    public function getBBEntity()
    {
        return $this->_bb_entity;
    }

    /**
     * Set BackBuilder Entity Object
     *
     * @codeCoverageIgnore
     * @param Object $entity
     * @return \BackBuilder\Importer\AConverter
     */
    public function setBBEntity($entity)
    {
        $this->_bb_entity = $entity;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param \BackBuilder\Importer\Importer $importer
     * @param array $config
     */
    public function beforeImport(Importer $importer, array $config) {}

    /**
     * @codeCoverageIgnore
     * @param \BackBuilder\Importer\Importer $importer
     * @param array $entities
     */
    public function afterEntitiesFlush(Importer $importer, array $entities) {}

    /**
     * Update Status and revision value
     * @param \BackBuilder\ClassContent\AClassContent $element
     * @return \BackBuilder\Bundle\WKImporter\Converter\ActuConverter
     */
    protected function _updateRevision(AClassContent $element)
    {
        $element->setRevision(1 + $element->getRevision())
                ->setState(AClassContent::STATE_NORMAL);

        return $this;
    }

    /**
     * Return a cleaned string
     * @param string $str
     * @return string
     */
    protected function _cleanText($str)
    {
        return  trim(html_entity_decode(html_entity_decode(String::toUTF8($str), ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'));
    }
    
    /**
     * Set the value of an scalar element
     * @codeCoverageIgnore
     */
    protected function _setScalar(AClassContent $element, $var, $value)
    {
        $element->$var = $this->_cleanText($value);
        return $this;
    }

    /**
     * Set the value of an Element\Text
     * @param \BackBuilder\ClassContent\AClassContent $element
     * @param string $value
     * @return \BackBuilder\Bundle\WKImporter\Converter\ActuConverter
     */
    protected function _setElementText(AClassContent $element, $value)
    {
        $element->value = $this->_cleanText($value);
        return $this->_updateRevision($element);
    }

    /**
     * Set the value of an Element\Date
     * @param \BackBuilder\ClassContent\AClassContent $element
     * @param string $value
     * @param \DateTimeZone $timezone
     * @param string $format
     * @return \BackBuilder\Bundle\WKImporter\Converter\ActuConverter
     */
    protected function _setElementDate(AClassContent $element, $value, \DateTimeZone $timezone = null, $format = 'Y-m-d H:i:s')
    {
        if (null !== $timezone) {
            $date = \DateTime::createFromFormat($format, $value, $timezone);
        } else {
            $date = \DateTime::createFromFormat($format, $value);
        }

        if (false !== $date) {
            $date->setTimezone(new \DateTimeZone("UTC"));
            return $this->_setElementText($element, $date->getTimestamp());
        }

        return $this->_setElementText($element, '');
    }
}