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

namespace BackBee\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BackBee\Console\ACommand;

/**
 * Install all bundles command.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class BundleInstallAllCommand extends ACommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('bundle:install_all')
            ->addOption('force', null, InputOption::VALUE_NONE, 'The install SQL will be executed against the DB')
            ->setDescription('Installs a bundle')
            ->setHelp(<<<EOF
The <info>%command.name%</info> installs all bundles:

   <info>php bundle:install_all MyBundle</info>
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

        $bbapp = $this->getContainer()->get('bbapp');

        foreach ($bbapp->getBundles() as $bundle) {
            $output->writeln('Installing bundle: '.$bundle->getId().'');

            $sqls = $bundle->getCreateQueries($bundle->getBundleEntityManager());

            if ($force) {
                $output->writeln('<info>Running install</info>');
                $bundle->install();
            }

            $output->writeln('<info>SQL executed: </info>'.PHP_EOL.implode(";".PHP_EOL, $sqls).'');
        }
    }
}
