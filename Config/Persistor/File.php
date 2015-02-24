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

namespace BackBee\Config\Persistor;

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

use Symfony\Component\Yaml\Yaml;

use BackBee\ApplicationInterface;
use BackBee\Config\Config;

/**
 *
 * @category    BackBee
 * @package     BackBee\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class File implements PersistorInterface
{
    /**
     * @var ApplicationInterface
     */
    private $app;

    /**
     * @see BackBee\Config\Persistor\PersistorInterface::__construct
     */
    public function __construct(ApplicationInterface $app)
    {
        $this->app = $app;
    }

    /**
     * @see BackBee\Config\Persistor\PersistorInterface::persist
     */
    public function persist(Config $config, array $configToPersist)
    {
        try {
            $success = @file_put_contents(
                $this->getConfigDumpRightDirectory($config->getBaseDir()).DIRECTORY_SEPARATOR.'config.yml',
                Yaml::dump($configToPersist)
            );
        } catch (\Exception $e) {
            $success = false;
        }

        return false !== $success;
    }

    /**
     * Returns path to the right directory to dump and save config.yml file
     *
     * @param string $baseDir config base directory
     *
     * @return string
     */
    private function getConfigDumpRightDirectory($baseDir)
    {
        $configDumpDir = $this->app->getRepository();
        if (ApplicationInterface::DEFAULT_CONTEXT !== $this->app->getContext()) {
            $configDumpDir .= DIRECTORY_SEPARATOR.$this->app->getContext();
        }

        $configDumpDir .= DIRECTORY_SEPARATOR.'Config';
        if (ApplicationInterface::DEFAULT_ENVIRONMENT !== $this->app->getEnvironment()) {
            $configDumpDir .= DIRECTORY_SEPARATOR.$this->app->getEnvironment();
        }

        $key = $this->app->getContainer()->get('bundle.loader')->getBundleIdByBaseDir($baseDir);
        if (null !== $key) {
            $configDumpDir .= DIRECTORY_SEPARATOR.'bundle'.DIRECTORY_SEPARATOR.$key;
        }

        if (!is_dir($configDumpDir) && false === @mkdir($configDumpDir, 0755, true)) {
            throw new \Exception('Unable to create config dump directory');
        }

        return $configDumpDir;
    }
}
