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

namespace BackBuilder\Importer\Connector;

use BackBuilder\BBApplication;
use BackBuilder\Importer\IImporterConnector;
use BackBuilder\Util\File;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @subpackage  Connector
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class FileSystem implements IImporterConnector
{
    /**
     * @var BackBuilder\BBApplication
     */
    private $_application;

    /**
     * The base directory where to look for files
     * @var string
     */
    private $_basedir;

    /**
     * Class constructor
     * @param \BackBuilder\BBApplication $application
     * @param array                      $config
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
