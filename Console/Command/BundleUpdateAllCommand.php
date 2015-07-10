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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update all bundles command.
 *
 * @category    BackBee
 * @copyright   Lp digital system
 * @author      Eric Chau <eric.chau@lp-digital.fr>
 */
class BundleUpdateAllCommand extends AbstractBundleCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('bundle:update_all')
            ->addOption('force', null, InputOption::VALUE_NONE, 'The update SQL will be executed against the DB')
            ->setDescription('Updates all bundles')
            ->setHelp(<<<EOF
The <info>%command.name%</info> updates all bundles:

   <info>php bundle:update_all </info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $methodToCall = $this->getCommandType().'Bundle';

        foreach ($this->getContainer()->get('bbapp')->getBundles() as $bundle) {
            $this->doExecute($bundle, $force, $methodToCall, $output);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandType()
    {
        return AbstractBundleCommand::UPDATE_COMMAND;
    }
}
