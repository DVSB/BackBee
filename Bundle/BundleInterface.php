<?php
namespace BackBuilder\Bundle;

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

use BackBuilder\Security\Acl\Domain\IObjectIdentifiable;

/**
 * BundleInterface which define somes methods to implements for BackBee bundles; it also define some constants
 *
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
interface BundleInterface extends IObjectIdentifiable
{
    /**
     * service id pattern (for bundle and bundle's config)
     */
    const BUNDLE_SERVICE_ID_PATTERN = 'bundle.%bundle_name%bundle';
    const CONFIG_SERVICE_ID_PATTERN = 'bundle.%bundle_id%.config';

    const BACKBEE_BUNDLE_DIRECTORY_NAME = 'bundle';

    /**
     * Config directories names
     */
    const CONFIG_DIRECTORY_NAME = 'Config';
    const OLD_CONFIG_DIRECTORY_NAME = 'Ressources';

    /**
     * [getId description]
     *
     * @return [type] [description]
     */
    public function getId();

    /**
     * [getBaseDirectory description]
     *
     * @return [type] [description]
     */
    public function getBaseDirectory();

    /**
     * [setBaseDirectory description]
     *
     * @param [type] $base_directory [description]
     */
    public function setBaseDirectory($base_directory);

    /**
     * [start description]
     *
     * @return [type] [description]
     */
    public function start();

    /**
     * [stop description]
     *
     * @return [type] [description]
     */
    public function stop();

    /**
     * [getApplication description]
     *
     * @return [type] [description]
     */
    public function getApplication();

    /**
     * [isStarted description]
     *
     * @return boolean [description]
     */
    public function isStarted();

    /**
     * [started description]
     *
     * @return [type] [description]
     */
    public function started();
}