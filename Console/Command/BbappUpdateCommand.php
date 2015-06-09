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

use BackBee\BBApplication;
use BackBee\Exception\BBException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use BackBee\Console\AbstractCommand;

/**
 * Update BBApp database.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class BbappUpdateCommand extends AbstractCommand
{
    
    /**
     * The current entity manager
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

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
        $this->em = $this->getContainer()->get('em');

        $this->checkBeforeUpdate();

        $this->em->getConfiguration()->getMetadataDriverImpl()->addPaths([
            $bbapp->getBBDir().'/Bundle',
            $bbapp->getBBDir().'/Cache/DAO',
            $bbapp->getBBDir().'/ClassContent',
            $bbapp->getBBDir().'/ClassContent/Indexes',
            $bbapp->getBBDir().'/Logging',
            $bbapp->getBBDir().'/NestedNode',
            $bbapp->getBBDir().'/Security',
            $bbapp->getBBDir().'/Site',
            $bbapp->getBBDir().'/Stream/ClassWrapper',
            $bbapp->getBBDir().'/Util/Sequence/Entity',
            $bbapp->getBBDir().'/Workflow',
        ]);

        $this->em->getConfiguration()->getMetadataDriverImpl()->addExcludePaths([
            $bbapp->getBBDir().'/ClassContent/Tests',
            $bbapp->getBBDir().'/NestedNode/Tests',
            $bbapp->getBBDir().'/Security/Tests',
            $bbapp->getBBDir().'/Util/Tests',
        ]);

        if (is_dir($bbapp->getBBDir().'/vendor')) {
            $this->em->getConfiguration()->getMetadataDriverImpl()->addExcludePaths([$bbapp->getBBDir().'/vendor']);
        }

        $sqls = $this->getUpdateQueries();

        if ($force) {
            $output->writeln('<info>Running update</info>');

            $metadata = $this->em->getMetadataFactory()->getAllMetadata();
            $schema = new SchemaTool($this->em);

            $this->em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=0');
            $schema->updateSchema($metadata, true);
            $this->em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        }

        $output->writeln('<info>SQL executed: </info>'.PHP_EOL.implode(";".PHP_EOL, $sqls).'');
    }

    /**
     * Checks against the db if section feature is already available for version > 1.1
     * @throws \BBException                 Raises if section features is not available
     */
    private function checkBeforeUpdate()
    {
        if (0 <= version_compare(BBApplication::VERSION, '1.1')) {
            $schemaManager = $this->em->getConnection()->getSchemaManager();
            $pageName = $this->em->getClassMetadata('BackBee\NestedNode\Page')->getTableName();
            $sectionName = $this->em->getClassMetadata('BackBee\NestedNode\Section')->getTableName();

            if (false === $schemaManager->tablesExist($sectionName) && true === $schemaManager->tablesExist($pageName)) {
                throw new BBException(sprintf('Table `%s` does not exist. Perhaps you should launch bbapp:upgradeToPageSection command before.', $sectionName));
            }
        }
    }

    /**
     * Get update queries.
     *
     * @return string[]
     */
    protected function getUpdateQueries()
    {
        $schema = new SchemaTool($this->em);

        $metadatas = $this->em->getMetadataFactory()->getAllMetadata();
        $sqls = $schema->getUpdateSchemaSql($metadatas, true);

        return $sqls;
    }
}
