<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Importer;

use BackBee\BBApplication;

/**
 * @category    BackBee
 * @package     BackBee\Importer
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
interface IImporterConnector
{
    public function __construct(BBApplication $application, array $values);

    /**
     * @return array|Traversable|Countable
     */
    public function find($string);

    /**
     * Called when connector is stopped
     */
    public function tearDown();
}
