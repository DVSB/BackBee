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

namespace BackBuilder\Command;

use BackBuilder\Console\ACommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
/**
 * Install BBApp assets
 *
 * @category    BackBuilder
 * @package     BackBuilder\Command
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class AclLoadCommand extends ACommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('acl:load')
            ->addArgument('file', InputArgument::REQUIRED, 'File')
            ->setDescription('Initialize ACL tables')
            ->addOption('truncate', 't', InputOption::VALUE_NONE, 'If set, truncates the acl tables')
            ->setHelp(<<<EOF
The <info>%command.name%</info> loads acl DB tables:

   <info>php acl:load</info>
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

        $file = $input->getArgument('file');

        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('File not found: %s', $file));
        }

        $output->writeln(sprintf('<info>Processing file: %s</info>', $file));

        $loader = $bbapp->getContainer()->get('security.acl_loader_yml');

        $loader->load(file_get_contents($file));
    }
}
