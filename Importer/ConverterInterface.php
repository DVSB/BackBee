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

namespace BackBee\Importer;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
interface ConverterInterface
{
    /**
     * Returns the values.
     *
     * @return array|Traversable|Countable
     */
    public function getRows(Importer $importer);

    /**
     * Convert each entries of the array into a BackBee Object Entity.
     *
     * @param array $values
     *
     * @return array composed by BackBee Object Entity
     */
    public function convert($values);

    /**
     * Function executed before the import started.
     *
     * @param \BackBee\Importer\Importer $importer
     * @param array                      $config
     */
    public function beforeImport(Importer $importer, array $config);

    /**
     * Function executed after each entity fush.
     *
     * @param \BackBee\Importer\Importer $importer
     * @param array                      $entities
     */
    public function afterEntitiesFlush(Importer $importer, array $entities);

    /**
     * Returns an existing or new object of BB Entity according to $identifier.
     *
     * @param string $identifier
     *
     * @return BackBee\ClassContent\AbstractClassContent $entity
     */
    public function getBBEntity($identifier);

    /**
     * Returns in an array list of every existing uid.
     *
     * @return array
     */
    public function getAvailableKeys();
}
