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

namespace BackBee\Session;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

/**
 * Allow to configure the session regarding the environment
 *
 * @author      Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 */
class PdoSessionHandlerFactory
{
    private $pdo;
    private $config;

    public function __construct(EntityManager $entityManager, array $config)
    {
        $this->pdo = $entityManager->getConnection()
            ->getWrappedConnection()
        ;
        $this->config = $config;
    }

    /**
     * @return PdoSessionHandler
     * @link https://github.com/symfony/symfony/blob/2.8/UPGRADE-2.6.md#httpfoundation PdoSessionHandler BC notes
     */
    public function createPdoHandler()
    {
        $this->config = array_merge($this->config, ['lock_mode' => PdoSessionHandler::LOCK_NONE]);
        return new PdoSessionHandler($this->pdo, $this->config);
    }
}
