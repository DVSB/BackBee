<?php

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

namespace BackBee\Command;

use BackBee\Console\ACommand;
use BackBee\Util\Dir;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears cache
 *
 * @category    BackBee
 * @package     BackBee\Command
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class CacheClearCommand extends ACommand
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
