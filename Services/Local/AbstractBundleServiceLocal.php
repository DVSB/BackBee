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

namespace BackBuilder\Services\Local;

/**
 * Abstract class for local RPC service provided by bundles
 *
 * @category    BackBuilder
 * @package     BackBuilder\Services
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
     * @var \BackBuilder\Bundle\ABundle
     */
    private $_bundle;

    /**
     * Returns the bundle providing services
     * @return \BackBuilder\Bundle\ABundle
     * @throws \BackBuilder\Services\Exception\ServicesException Occures if the bundle has not been specified
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
     * @param string $bundleId
     * @return \BackBuilder\Services\Local\AbstractBundleServiceLocal
     * @throws \BackBuilder\Services\Exception\ServicesException Occures if the bundle is unknown or invalid
     */
    public function setBundle($bundleId = null)
    {
        if (null === $bundleId || null === $bundle = $this->getApplication()->getBundle($bundleId)) {
            throw new \BackBuilder\Services\Exception\ServicesException(sprintf('Unknown bundle with id `%s`.', $bundleId));
        }

        if (false === ($bundle instanceof \BackBuilder\Bundle\ABundle)) {
            throw new \BackBuilder\Services\Exception\ServicesException(sprintf('Invalid bundle with id `%s`.', $bundleId));
        }

        $this->_bundle = $bundle;
        return $this;
    }

}