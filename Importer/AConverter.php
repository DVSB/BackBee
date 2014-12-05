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

use BackBuilder\ClassContent\AClassContent;
use BackBuilder\Util\String;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @copyright   Lp digital system
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
     * @var \BackBuilder\Importer\Importer
     */
    protected $_importer;

    /**
     * Every keys
     */
    protected $_availableKeys = null;
    /**
     * Class constructor
     * @param \BackBuilder\Importer\Importer $importer
     */
    public function __construct(Importer $importer = null)
    {
        $this->_importer = $importer;
    }

    /**
     * return the BackBuilder Entity Object
     *
     * @return Object
     */
    public function getBBEntity($identifier)
    {
        return new $this->_bb_entity($identifier);
    }

    /**
     * Set BackBuilder Entity Object
     *
     * @param  Object                           $entity
     * @return \BackBuilder\Importer\AConverter
     */
    public function setBBEntity($entity)
    {
        $this->_bb_entity = $entity;

        return $this;
    }

    public function beforeImport(Importer $importer, array $config)
    {
    }

    public function afterEntitiesFlush(Importer $importer, array $entities)
    {
    }

    public function onImportationFinish()
    {
    }

    public function getAvailableKeys()
    {
        if (null === $this->_availableKeys) {
            $this->_availableKeys = array();
        }

        return $this->_availableKeys;
    }

    /**
     * Update Status and revision value
     * @param  \BackBuilder\ClassContent\AClassContent                $element
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
     * @param  string $str
     * @return string
     */
    protected function _cleanText($str)
    {
        return trim(html_entity_decode(html_entity_decode(String::toUTF8($str), ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Set the value of an scalar element
     */
    protected function _setScalar(AClassContent $element, $var, $value)
    {
        $element->$var = $this->_cleanText($value);

        return $this;
    }

    /**
     * Set the value of an Element\Text
     * @param  \BackBuilder\ClassContent\AClassContent                $element
     * @param  string                                                 $value
     * @return \BackBuilder\Bundle\WKImporter\Converter\ActuConverter
     */
    protected function _setElementText(AClassContent $element, $value)
    {
        $element->value = $this->_cleanText($value);

        return $this->_updateRevision($element);
    }

    /**
     * Set the value of an Element\Date
     * @param  \BackBuilder\ClassContent\AClassContent                $element
     * @param  string                                                 $value
     * @param  \DateTimeZone                                          $timezone
     * @param  string                                                 $format
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
