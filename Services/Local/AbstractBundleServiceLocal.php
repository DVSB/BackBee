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

namespace BackBee\Services\Local;

/**
 * Abstract class for local RPC service provided by bundles
 *
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class AbstractBundleServiceLocal extends AbstractServiceLocal
{
    /**
     * The id of the bundle providing services
     * @var string
     */
    protected $_bundle_id;

    /**
     * The bundle providing services
     * @var \BackBee\Bundle\ABundle
     */
    private $_bundle;

    /**
     * Returns the bundle providing services
     * @return \BackBee\Bundle\ABundle
     * @throws \BackBee\Services\Exception\ServicesException Occures if the bundle has not been specified
     */
    public function getBundle()
    {
        if (null === $this->_bundle) {
            $this->setBundle($this->_bundle_id);
        }

        return $this->_bundle;
    }

    /**
     * Sets the bundle providing services by its id
     * @param  string                                             $bundleId
     * @return \BackBee\Services\Local\AbstractBundleServiceLocal
     * @throws \BackBee\Services\Exception\ServicesException      Occures if the bundle is unknown or invalid
     */
    public function setBundle($bundleId = null)
    {
        if (null === $bundleId || null === $bundle = $this->getApplication()->getBundle($bundleId)) {
            throw new \BackBee\Services\Exception\ServicesException(sprintf('Unknown bundle with id `%s`.', $bundleId));
        }

        if (false === ($bundle instanceof \BackBee\Bundle\ABundle)) {
            throw new \BackBee\Services\Exception\ServicesException(sprintf('Invalid bundle with id `%s`.', $bundleId));
        }

        $this->_bundle = $bundle;

        return $this;
    }
}
