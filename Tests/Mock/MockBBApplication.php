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

namespace BackBee\Tests\Mock;

use org\bovigo\vfs\vfsStream;
use BackBee\BBApplication;
use BackBee\Site\Site;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MockBBApplication extends BBApplication
{
    private $_container;
    private $_context;
    private $_debug;
    private $_isinitialized;
    private $_isstarted;
    private $_autoloader;
    private $_bbdir;
    private $_mediadir;
    private $_repository;
    private $_base_repository;
    private $_resourcedir;
    private $_starttime;
    private $_storagedir;
    private $_tmpdir;
    private $_bundles;
    private $_classcontentdir;
    private $_theme;
    private $_overwrite_config;

    /**
     * The mock base directory.
     *
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $_mock_basedir;

    /**
     * Mock the BBApplication class constructor.
     *
     * @param string  $context
     * @param boolean $debug
     * @param boolean $overwrite_config
     */
    public function __construct($context = null, $environment = false, $overwrite_config = false, array $mockConfig = null)
    {
        $this->mockInitStructure($mockConfig);
        parent::__construct($context, $environment, $overwrite_config);
    }

    public function getBBDir()
    {
        if (null === $this->_bbdir) {
            $r = new \ReflectionClass('\BackBee\BBApplication');
            $this->_bbdir = dirname($r->getFileName());
        }

        return $this->_bbdir;
    }

    /**
     * Get vendor dir.
     *
     * @return string
     */
    public function getVendorDir()
    {
        return $this->getBBDir().'/vendor';
    }

    /**
     * Mock the merhod returning the base repository directory.
     *
     * @return string
     */
    public function getBaseRepository()
    {
        return vfsStream::url('repositorydir');
    }

    public function getCacheDir()
    {
        return vfsStream::url('repositorydir/cache');
    }

    /**
     * Initilizes the mock structure.
     *
     * @return \BackBee\Tests\Mock\MockBBApplication
     */
    protected function mockInitStructure(array $mockConfig = null)
    {
        if (null === $mockConfig) {
            $mockConfig = array(
                'ClassContent' => [],
                'Config' => array(
                    'bootstrap.yml' => file_get_contents(__DIR__.'/../Config/bootstrap.yml'),
                    'config.yml' => file_get_contents(__DIR__.'/../Config/config.yml'),
                    'doctrine.yml' => file_get_contents(__DIR__.'/../Config/doctrine.yml'),
                    'logging.yml' => file_get_contents(__DIR__.'/../Config/logging.yml'),
                    'security.yml' => file_get_contents(__DIR__.'/../Config/security.yml'),
                    'services.yml' => file_get_contents(__DIR__.'/../Config/services.yml'),
                ),
                'Layouts' => ['default.twig' => '<html></html>'],
                'Data' => array(
                    'Media' => array(),
                    'Storage' => array(),
                    'Tmp' => array(),
                ),
                'Ressources' => array(),
                'cache' => array(
                    'Proxies' => array(),
                ),
            );
        }

        vfsStream::umask(0000);
        $this->_mock_basedir = vfsStream::setup('repositorydir', 0777, $mockConfig);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function start(Site $site = null)
    {
        $this->_isstarted = true;
    }

    /**
     */
    public function setIsStarted($isStarted)
    {
        $this->_isstarted = $isStarted;
    }

    /**
     * @return boolean
     */
    public function isStarted()
    {
        return (true === $this->_isstarted);
    }
}
