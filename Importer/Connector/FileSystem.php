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

namespace BackBee\Importer\Connector;

use BackBee\BBApplication;
use BackBee\Importer\IImporterConnector;
use BackBee\Utils\File\File;

/**
 * @category    BackBee
 * @package     BackBee\Importer
 * @subpackage  Connector
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class FileSystem implements IImporterConnector
{
    /**
     * @var BackBee\BBApplication
     */
    private $_application;

    /**
     * The base directory where to look for files
     * @var string
     */
    private $_basedir;

    /**
     * Class constructor
     * @param \BackBee\BBApplication $application
     * @param array                  $config
     */
    public function __construct(BBApplication $application, array $config)
    {
        $this->_application = $application;
        $this->_config = $config;

        if (true === array_key_exists('basedir', $config)) {
            $this->_basedir = $config['basedir'];
            File::resolveFilepath($this->_basedir, null, array('include_path' => $this->_application->getRepository()));
        } else {
            $this->_basedir = $this->_application->getRepository();
        }
    }

    /**
     * Get the root dir
     *
     * @return string
     */
    public function getBaseDir()
    {
        return $this->_basedir;
    }

    /**
     * Return the path files according to the provided pattern
     *
     * @param  string $pattern file pattern
     * @return array
     */
    public function find($pattern)
    {
        $values = glob($this->_basedir.DIRECTORY_SEPARATOR.$pattern);
        sort($values);

        return $values;
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
    }
}
