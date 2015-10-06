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

use BackBee\Bundle\BundleInterface;
use BackBee\Console\AbstractCommand;
use BackBee\DependencyInjection\ContainerInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @category    BackBee
 * @copyright   Lp digital system
 * @author      Eric Chau <eric.chau@lp-digital.fr>
 */
abstract class AbstractBundleCommand extends AbstractCommand
{
    const INSTALL_COMMAND = 'install';
    const UPDATE_COMMAND = 'update';

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = strtr($input->getArgument('name'), '/', '\\');

        if (!$this->getContainer()->has('bundle.'.$name)) {
            throw new \InvalidArgumentException(sprintf('Not a valid bundle: %s', $name));
        }

        $bundle = $this->getContainer()->get('bundle.'.$name);

        $this->doExecute($bundle, $input->getOption('force'), $this->getCommandType().'Bundle', $output);
    }

    protected function doExecute(BundleInterface $bundle, $force, $methodToCall, OutputInterface $output)
    {
        $starttime = microtime(true);
        $message = null;

        if ($force) {
            $message = "\n\n".sprintf(
                ' ✓  %s of bundle "%s" started.',
                ucfirst($this->getCommandType()),
                $bundle->getId()
            )."\n\n";
        } else {
            $message = "\n\n".sprintf(
                ' ✓  Getting information about %s of bundle "%s"...',
                $this->getCommandType(),
                $bundle->getId()
            )."\n\n";
        }

        $output->writeln($message);

        $logs = $this->getContainer()->get('bundle.loader')->$methodToCall($bundle, $force);

        if ($force) {
            $output->writeln(sprintf(
                ' ✓  %s of bundle "%s" completed in %s.',
                ucfirst($this->getCommandType()),
                $bundle->getId(),
                number_format(microtime(true) - $starttime, 3).' s'
            )."\n\n");
        }

        $output->writeln('SQL:'.(0 < count($logs['sql']) ? '' : ' -'));
        foreach ($logs['sql'] as $sql) {
            $output->writeln($sql);
        }

        unset($logs['sql']);

        foreach ($logs as $key => $other) {
            foreach ((array) $other as $message) {
                $output->writeln(sprintf('[%s] %s', ucfirst($key), $message));
            }
        }
    }

    /**
     * Returns self::INSTALL_COMMAND (="install") or self::UPDATE_COMMAND to specify its type.
     *
     * @return string
     */
    abstract protected function getCommandType();
}
