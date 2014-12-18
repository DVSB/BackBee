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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BackBee\Job\NestedNodeLRCalculateJob;

/**
 * Update BBApp database
 *
 * @category    BackBee
 * @package     BackBee\Command
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class NestedNodeLRCalculateCommand extends ACommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('nestednode:job:lrcalculate')
            ->addOption('nodeId', null, InputOption::VALUE_REQUIRED)
            ->addOption('nodeClass', null, InputOption::VALUE_REQUIRED)
            ->addOption('first', null, InputOption::VALUE_REQUIRED)
            ->addOption('delta', null, InputOption::VALUE_REQUIRED)
            ->setDescription('Update nested node left node and right node value')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $job = new NestedNodeLRCalculateJob();
        $job->setEntityManager($this->getContainer()->get('em'));
        $job->run(array(
            'nodeId'    => $input->getOption('nodeId'),
            'nodeClass' => $input->getOption('nodeClass'),
            'first'     => $input->getOption('first'),
            'delta'     => $input->getOption('delta'),
        ));
    }
}
