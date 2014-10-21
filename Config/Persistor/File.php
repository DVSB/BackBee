<?php
namespace BackBuilder\Config\Persistor;

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

use BackBuilder\Config\Config;
use BackBuilder\Config\Persistor\PersistorInterface;
use BackBuilder\IApplication as ApplicationInterface;

use Symfony\Component\Yaml\Yaml;

/**
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class File implements PersistorInterface
{
    /**
     * [$application description]
     *
     * @var [type]
     */
    private $application;

    /**
     * @see BackBuilder\Config\Persistor\PersistorInterface::__construct
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->application = $application;
    }

    /**
     * @see BackBuilder\Config\Persistor\PersistorInterface::persist
     */
    public function persist(Config $config, array $config_to_persist)
    {
        try {
            $success = @file_put_contents(
                $this->getConfigDumpRightDirectory($config->getBaseDir()) . DIRECTORY_SEPARATOR . 'config.yml',
                Yaml::dump($config_to_persist)
            );
        } catch (\Exception $e) {
            $success = false;
        }

        return false !== $success;
    }

    /**
     * Returns path to the right directory to dump and save config.yml file
     *
     * @param  string $base_directory config base directory
     *
     * @return string
     */
    private function getConfigDumpRightDirectory($base_directory)
    {
        $config_dump_directory = $this->application->getRepository();
        if (ApplicationInterface::DEFAULT_CONTEXT !== $this->application->getContext()) {
            $config_dump_directory .= DIRECTORY_SEPARATOR . $this->application->getContext();
        }

        $config_dump_directory .= DIRECTORY_SEPARATOR . 'Config';
        if (ApplicationInterface::DEFAULT_ENVIRONMENT !== $this->application->getEnvironment()) {
            $config_dump_directory .= DIRECTORY_SEPARATOR . $this->application->getEnvironment();
        }

        if (1 === preg_match('#(bundle/[a-zA-Z]+Bundle)#', $base_directory, $matches)) {
            $config_dump_directory .= DIRECTORY_SEPARATOR . $matches[1];
        }

        if (false === is_dir($config_dump_directory) && false === @mkdir($config_dump_directory, 0755, true)) {
            throw new \Exception('Unable to create config dump directory');
        }

        return $config_dump_directory;
    }
}