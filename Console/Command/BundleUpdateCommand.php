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

namespace BackBee\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Update bundle command.
 *
 * @category    BackBee
 * @copyright   Lp digital system
 * @author      Eric Chau <eric.chau@lp-digital.fr>
 */
class BundleUpdateCommand extends AbstractBundleCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('bundle:update')
            ->addArgument('name', InputArgument::REQUIRED, 'A bundle name')
            ->addOption('force', null, InputOption::VALUE_NONE, 'The update SQL will be executed against the DB')
            ->setDescription('Updates a bundle')
            ->setHelp(<<<EOF
The <info>%command.name%</info> updates a bundle:

   <info>php bundle:update MyBundle</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandType()
    {
        return AbstractBundleCommand::UPDATE_COMMAND;
    }
}
