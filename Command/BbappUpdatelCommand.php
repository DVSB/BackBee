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

namespace BackBee\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use BackBee\Console\ACommand;

/**
 * Update BBApp database
 *
 * @category    BackBee
 * @package     BackBee\Command
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class BbappUpdatelCommand extends ACommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('bbapp:update')
            ->addOption('force', null, InputOption::VALUE_NONE, 'The update SQL will be executed against the DB')
            ->setDescription('Updated bbapp')
            ->setHelp(<<<EOF
The <info>%command.name%</info> updates app:

   <info>php bbapp:update</info>
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
        $em = $this->getContainer()->get('em');

        $em->getConfiguration()->getMetadataDriverImpl()->addPaths(array(
            $bbapp->getBBDir().'/Bundle',
            $bbapp->getBBDir().'/Cache/DAO',
            $bbapp->getBBDir().'/ClassContent',
            $bbapp->getBBDir().'/ClassContent/Indexes',
            $bbapp->getBBDir().'/Logging',
            $bbapp->getBBDir().'/NestedNode',
            $bbapp->getBBDir().'/Security',
            $bbapp->getBBDir().'/Site',
            $bbapp->getBBDir().'/Site/Metadata',
            $bbapp->getBBDir().'/Stream/ClassWrapper',
            $bbapp->getBBDir().'/Theme',
            $bbapp->getBBDir().'/Util/Sequence/Entity',
            $bbapp->getBBDir().'/Workflow',
        ));

        $sqls = $this->getUpdateQueries($em);

        if ($force) {
            $output->writeln('<info>Running update</info>');

            $metadata = $em->getMetadataFactory()->getAllMetadata();
            $schema = new SchemaTool($em);

            $em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=0');
            $schema->updateSchema($metadata, true);
            $em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        }

        $output->writeln('<info>SQL executed: </info>'.PHP_EOL.implode(";".PHP_EOL, $sqls).'');
    }

    /**
     * Get update queries
     * @param  EntityManager $em
     * @return String[]
     */
    protected function getUpdateQueries(EntityManager $em)
    {
        $schema = new SchemaTool($em);

        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $sqls = $schema->getUpdateSchemaSql($metadatas, true);

        return $sqls;
    }
}
