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
use Symfony\Component\Console\Output\OutputInterface;

use BackBee\Console\AbstractCommand;
use BackBee\Utils\File\Dir;

/**
 * Clears cache.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class CacheClearCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDefinition(array(
            ))
            ->setDescription('Clears application cache')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command clears the application cache for a given environment
and debug mode:

<info>php %command.full_name% --env=dev</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bbapp = $this->getContainer()->get('bbapp');
        $output->writeln(sprintf('Clearing the cache for the <info>%s</info> environment with debug <info>%s</info>', $bbapp->getEnvironment(), var_export($bbapp->isDebugMode(), true)));

        $cacheDir = $this->getContainer()->getParameter("bbapp.cache.dir");

        $oldCacheDir = $cacheDir.'_old';

        if (file_exists($oldCacheDir)) {
            Dir::delete($oldCacheDir);
        }

        Dir::move($cacheDir, $oldCacheDir);

        mkdir($cacheDir);

        if (file_exists($oldCacheDir)) {
            Dir::delete($oldCacheDir);
        }
    }
}
