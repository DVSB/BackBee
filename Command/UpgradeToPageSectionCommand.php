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

use BackBuilder\BBApplication;
use BackBuilder\Console\ACommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Tools\SchemaTool;
use BackBuilder\Exception\BBException;
use BackBuilder\Site\Site;
use BackBuilder\Util\Arrays;
use Doctrine\DBAL\Types\Type;

/**
 * Upgrade BackBee 0.10 to BackBee 0.11 data structure
 *
 * @category    BackBuilder
 * @package     BackBuilder\Command
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class UpgradeToPageSectionCommand extends ACommand
{

    /**
     * Force database storage upgrade
     * @var boolean
     */
    private $overrideExisting;

    /**
     * Skip the updating of the existing nested pages data
     * @var boolean
     */
    private $skipNodesUpdate;

    /**
     * The memory limit
     * @var string
     */
    private $memoryLimit;

    /**
     * Output interface
     * @var \Symfony\Component\Console\Output\OutputInterface 
     */
    private $output;

    /**
     * The current entity manager
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * The required fields for table `page`
     * @var array
     */
    private $requiredFields;

    /**
     * @var int
     */
    private $step = 1000;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('upgrade:0.10:0.11')
                ->addOption('force', 'f', InputOption::VALUE_NONE, 'The database storage will be overrided against the existing one.')
                ->addOption('skip-nodes-update', null, InputOption::VALUE_NONE, 'Skip the updating of the existing nested pages data.')
                ->addOption('memory-limit', 'm', InputOption::VALUE_OPTIONAL, 'The memory limit to set.')
                ->setDescription('Upgrade BackBee 0.10 to BackBee 0.11 data structure')
                ->setHelp(<<<EOF
This command introduce section feature and updates data storage of pages from v0.10 to v0.11 and further:

   <info>php %command.name%</info>
EOF
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime();

        $this->overrideExisting = $input->getOption('force');
        $this->skipNodesUpdate = $input->getOption('skip-nodes-update');
        $this->em = $this->getContainer()->get('em');
        $this->output = $output;

        if (null !== $input->getOption('memory-limit')) {
            ini_set('memory_limit', $input->getOption('memory-limit'));
        }

        $this->checksBackBeeVersion()
                ->checksSectionTable()
                ->checksPageTable()
                ->updateNodes('BackBuilder\NestedNode\Page')
                ->updateSectionTable()
                ->updateNodes('BackBuilder\NestedNode\Section');

        $this->output->writeln(sprintf('<info>UPGRADE DONE IN %d s.</info>', ceil((microtime() - $startTime) / 1000)));
        $this->output->writeln(sprintf(' - You should launch bbapp:update command.'));
    }

    /**
     * Checks for BackBee version, at least 0.11.0 is required
     * @return \BackBuilder\Command\upgradeFrom0_10To0_11Command
     * @throws \BackBuilder\Exception\BBException
     */
    private function checksBackBeeVersion()
    {
        $this->output->writeln('<info>Checking BackBee instance</info>');
        $this->output->write(sprintf(' - BackBee version: %s - ', BBApplication::VERSION));

        if (0 > version_compare(BBApplication::VERSION, '0.11.0')) {
            $this->output->writeln("<error>Failed</error>");
            throw new BBException(sprintf('This command needs at least BackBee v0.11.0 installed, gets BackBee v%s.%sPlease upgrade your distribution.', BBApplication::VERSION, PHP_EOL));
        }

        $this->output->writeln('<info>OK</info>');
        return $this;
    }

    /**
     * Checks for existing table `section`, throw exception if overrideExisting is set to FALSE
     * @return \BackBuilder\Command\upgradeFrom0_10To0_11Command
     * @throws \BackBuilder\Exception\BBException
     */
    private function checksSectionTable()
    {
        $schemaManager = $this->em->getConnection()->getSchemaManager();
        $tableName = $this->em->getClassMetadata('BackBuilder\NestedNode\Section')->getTableName();

        $this->output->write(sprintf(' - New table `%s` - ', $tableName));

        if (true === $this->overrideExisting) {
            $this->output->writeln('<comment>Skipped</comment> (--force option set)');
            return $this;
        }

        if (true === $schemaManager->tablesExist($tableName)) {
            $this->output->writeln("<error>Failed</error>");
            throw new BBException(sprintf('Table `%s` already exists, please use --force option to override it.', $tableName));
        }

        $this->output->writeln('<info>OK</info>');
        return $this;
    }

    /**
     * Checks for required fields in table `page`
     * @return \BackBuilder\Command\upgradeFrom0_10To0_11Command
     * @throws \BackBuilder\Exception\BBException
     */
    private function checksPageTable()
    {
        $schemaManager = $this->em->getConnection()->getSchemaManager();
        $tableName = $this->em->getClassMetadata('BackBuilder\NestedNode\Page')->getTableName();

        $this->output->write(sprintf(' - Existing table `%s` - ', $tableName));

        if (false === $schemaManager->tablesExist($tableName)) {
            $this->output->writeln("<error>Failed</error>");
            throw new BBException(sprintf('Unknown table `%s`, please run BackBee installation script.', $tableName));
        }

        $sectionMeta = $this->em->getClassMetadata('BackBuilder\NestedNode\Section');
        $this->requiredFields['uid'] = $sectionMeta->getColumnName('_uid');
        $this->requiredFields['leftnode'] = $sectionMeta->getColumnName('_leftnode');
        $this->requiredFields['rightnode'] = $sectionMeta->getColumnName('_rightnode');
        $this->requiredFields['level'] = $sectionMeta->getColumnName('_level');
        $this->requiredFields['created'] = $sectionMeta->getColumnName('_created');
        $this->requiredFields['modified'] = $sectionMeta->getColumnName('_modified');
        $this->requiredFields['site_uid'] = $sectionMeta->getSingleAssociationJoinColumnName('_site');
        $this->requiredFields['root_uid'] = $sectionMeta->getSingleAssociationJoinColumnName('_root');
        $this->requiredFields['parent_uid'] = $sectionMeta->getSingleAssociationJoinColumnName('_parent');

        $existingFields = array_keys($schemaManager->listTableColumns($tableName));
        $missingFields = array_diff($this->requiredFields, $existingFields);

        if (0 < count($missingFields)) {
            $this->output->writeln("<error>Failed</error>");
            throw new BBException(sprintf(
                    'Following required fields are missing in table `%s`: `%s`.%sCannot upgrade database storage anymore.', 
                    $tableName, 
                    implode('`, `', $missingFields), PHP_EOL)
                );
        }

        $this->output->writeln('<info>OK</info>');
        return $this;
    }

    /**
     * Updates nested data from existing pages
     * @param string $classname
     * @return \BackBuilder\Command\upgradeFrom0_10To0_11Command
     */
    private function updateNodes($classname)
    {
        $this->output->writeln(sprintf('<info>Updating nested data for %s.</info>', $classname));

        if (true === $this->skipNodesUpdate) {
            $this->output->writeln(' - <comment>Skipped</comment> (option --skip-nodes-update set)');
            return $this;
        }

        $sites = $this->em->getRepository('BackBuilder\Site\Site')->findAll();
        if (0 === count($sites)) {
            $this->output->writeln(' - None site found - <comment>Skipped</comment>');
            return $this;
        }

        $progress = $this->getHelperSet()->get('progress');
        $progress->start($this->output, $this->countNodes($classname));

        $this->em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        foreach ($sites as $site) {
            foreach ($this->getPageRoot($site, $classname) as $root_uid) {
                $this->updateTreeNatively($root_uid, $classname);
            }
        }
        $this->em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        $progress->finish();

        return $this;
    }

    /**
     * Creates or updates (if $this->overrideExisting set to TRUE) tbe section and populates it
     * @return \BackBuilder\Command\upgradeFrom0_10To0_11Command
     */
    private function updateSectionTable()
    {
        $this->em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=0');

        $schemaTool = new SchemaTool($this->em);
        $schemaManager = $this->em->getConnection()->getSchemaManager();
        $sectionMeta = $this->em->getClassMetadata('BackBuilder\NestedNode\Section');
        $tableSection = $sectionMeta->getTableName();

        if (false === $schemaManager->tablesExist($tableSection)) {
            $this->output->writeln(sprintf('<info>Creating table `%s`</info>', $tableSection));
            $schemaTool->createSchema(array($sectionMeta));
        } else {
            if (false === $this->overrideExisting) {
                return $this;
            }

            $this->output->writeln(sprintf('<info>Updating table `%s`</info>',$tableSection));
            $schemaTool->updateSchema(array($sectionMeta), true);
            $this->em->getConnection()->executeQuery(sprintf('DELETE FROM %s WHERE 1=1', $tableSection));
        }

        $query = sprintf(
                'INSERT INTO %s (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s) ',
                $tableSection,
                $this->requiredFields['uid'],
                $this->requiredFields['root_uid'],
                $this->requiredFields['parent_uid'],
                $this->requiredFields['leftnode'],
                $this->requiredFields['rightnode'],
                $this->requiredFields['level'],
                $this->requiredFields['created'],
                $this->requiredFields['modified'],
                $sectionMeta->getSingleAssociationJoinColumnName('_page'),
                $this->requiredFields['site_uid']
        );
        
        $pageMeta = $this->em->getClassMetadata('BackBuilder\NestedNode\Page');
        $tablePage = $pageMeta->getTableName();

        $query .= sprintf('SELECT %s, %s, %s, %s, %s, %s, %s, %s, %s, %s FROM %s WHERE %s > %s + 1',
                $this->requiredFields['uid'],
                $this->requiredFields['root_uid'],
                $this->requiredFields['parent_uid'],
                $this->requiredFields['leftnode'],
                $this->requiredFields['rightnode'],
                $this->requiredFields['level'],
                $this->requiredFields['created'],
                $this->requiredFields['modified'],
                $this->requiredFields['uid'],
                $this->requiredFields['site_uid'],
                $tablePage,
                $this->requiredFields['rightnode'],
                $this->requiredFields['leftnode']
        );

        $result = $this->em->getConnection()->executeQuery($query);
        $this->em->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        $this->output->writeln(sprintf(' - %d section rows created', $result->rowCount()));

        return $this;
    }

    /**
     * Returns the number of nested nodes
     * @param string $classname
     * @return int
     */
    private function countNodes($classname)
    {
        $query = sprintf(
                'SELECT COUNT(%s) FROM %s', 
                $this->requiredFields['uid'], 
                $this->em->getClassMetadata($classname)->getTableName()
        );

        $count = $this->em->getConnection()
                        ->executeQuery($query)
                        ->fetchAll(\PDO::FETCH_COLUMN);

        return array_pop($count);
    }

    /**
     * Retrieves root nodes for $site
     * @param Site  $site   The site we are looking for root nodes
     * @param string $classname
     * @return array        The root nodes found
     */
    private function getPageRoot(Site $site, $classname)
    {
        $query = sprintf(
                'SELECT %s FROM %s WHERE %s = ? AND %s IS NULL', 
                $this->requiredFields['uid'], 
                $this->em->getClassMetadata($classname)->getTableName(), 
                $this->requiredFields['site_uid'], 
                $this->requiredFields['parent_uid']
        );

        return $this->em->getConnection()
                        ->executeQuery($query, array($site->getUid()), array(Type::STRING))
                        ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Updates nodes information of a tree
     * @param string $node_uid  The starting point in the tree
     * @param string $classname
     * @param int $leftnode     Optional, the first value of left node
     * @param int $level        Optional, the first value of level
     * @return \StdClass
     */
    private function updateTreeNatively($node_uid, $classname, $leftnode = 1, $level = 0)
    {
        $node = new \StdClass();
        $node->uid = $node_uid;
        $node->leftnode = $leftnode;
        $node->rightnode = $leftnode + 1;
        $node->level = $level;

        $start = 0;
        $numChildren = $this->getCountChildren($node_uid, $classname);
        while($start < $numChildren) {
            foreach ($children = $this->getNodeChildren($node_uid, $classname, $start, $this->step) as $child_uid) {
                $child = $this->updateTreeNatively($child_uid, $classname, $leftnode + 1, $level + 1);
                $node->rightnode = $child->rightnode + 1;
                $leftnode = $child->rightnode;
                unset($child);
            }
            unset($children);
            $start += $this->step;
        }

        $this->updatePageNodes($node->uid, $node->leftnode, $node->rightnode, $node->level, $classname);

        return $node;
    }

    /**
     * Returns number of the children of $node_uid
     * @param string $node_uid  The node uid to look for children
     * @param string $classname
     * @return array
     */
    private function getCountChildren($node_uid, $classname)
    {
        $query = sprintf(
                'SELECT COUNT(%s) FROM %s WHERE %s = ?', 
                $this->requiredFields['uid'], 
                $this->em->getClassMetadata($classname)->getTableName(), 
                $this->requiredFields['parent_uid']
        );

        $result = $this->em->getConnection()
                ->executeQuery($query, array($node_uid), array(Type::STRING))
                ->fetchAll(\PDO::FETCH_COLUMN);

        return array_pop($result);
    }

    /**
     * Returns an array of uid of the children of $node_uid
     * @param string $node_uid  The node uid to look for children
     * @param string $classname
     * @param int $start
     * @param int $limit
     * @return array
     */
    private function getNodeChildren($node_uid, $classname, $start = 0, $limit = 1000)
    {
        $query = sprintf(
                'SELECT %s FROM %s WHERE %s = ? ORDER BY %s ASC, %s DESC LIMIT %d, %d', 
                $this->requiredFields['uid'], 
                $this->em->getClassMetadata($classname)->getTableName(), 
                $this->requiredFields['parent_uid'],
                $this->requiredFields['leftnode'],
                $this->requiredFields['modified'],
                $start,
                $limit
        );

        $result = $this->em->getConnection()
                ->executeQuery($query, array($node_uid), array(Type::STRING))
                ->fetchAll();

        return Arrays::array_column($result, $this->requiredFields['uid']);
    }

    /**
     * Updates nodes information for $node_uid
     * @param string $node_uid
     * @param int $leftnode
     * @param int $rightnode
     * @param int $level
     * @param string $classname
     */
    private function updatePageNodes($node_uid, $leftnode, $rightnode, $level, $classname)
    {
        $progress = $this->getHelperSet()->get('progress');
        $progress->advance();

        $query = sprintf(
                'UPDATE %s SET %s = ?, %s = ?, %s = ? WHERE %s = ?', 
                $this->em->getClassMetadata($classname)->getTableName(), 
                $this->requiredFields['leftnode'], 
                $this->requiredFields['rightnode'], 
                $this->requiredFields['level'], 
                $this->requiredFields['uid']
        );

        $params = array(
            $leftnode,
            $rightnode,
            $level,
            $node_uid,
        );
        
        $types = array(
            Type::INTEGER,
            Type::INTEGER,
            Type::INTEGER,
            Type::STRING,
        );

        $this->em->getConnection()->executeQuery($query, $params, $types);
    }

}
