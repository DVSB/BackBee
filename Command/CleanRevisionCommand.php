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
 * This command allow us to clean revision table depending on provided criterias.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class CleanRevisionCommand extends ACommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('revision:clean')
            ->setDescription('Remove contents revisions according to provided criterias')
            ->addOption(
                'created',
                null,
                InputOption::VALUE_REQUIRED,
                'Remove every revisions with outdated created date comparing to provided created date'
            )
            ->addOption(
                'revision',
                null,
                InputOption::VALUE_REQUIRED,
                'Keep every x lasts revisions of each content and remove the rest'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conn = $this->getContainer()->get('bbapp')->getEntityManager()->getConnection();
        $created = $input->getOption('created');
        $revision = $input->getOption('revision');

        if (null ===  $created && null === $revision) {
            $output->writeln("\nYou have to provide atleast one criteria (`created` and/or `revision`).\n");

            return;
        }

        $starttime = microtime(true);
        $revisionCount = $conn->executeQuery("SELECT count(*) as count FROM revision")->fetch();
        $revisionCount = $revisionCount['count'];

        $output->writeln("\nBefore cleaning, revision table contains $revisionCount row(s).\n");

        $conn->executeQuery("DELETE FROM revision WHERE content_uid IS NULL")->execute();

        if (null !== $created) {
            if (1 === preg_match('#^-[0-9]+ (months?|days?|years?)$#', $created)) {
                $datetime = new \DateTime();
                $datetime->modify($created);
                $created = $datetime->format('Y-m-d H:i:s');
            } else {
                $created = date('Y-m-d H:i:s', strtotime($input->getOption('created')));
            }

            $conn->executeQuery("DELETE FROM revision WHERE created < '$created'")->execute();
        }

        if (null !== $revision) {
            $contentUids = $conn->executeQuery('SELECT DISTINCT content_uid FROM revision')->fetchAll();

            $contentCount = 1;
            foreach ($contentUids as $contentUid) {
                $time = microtime(true);
                $validRevisions = array();
                $contentUid = $contentUid['content_uid'];

                $query = "SELECT uid
                          FROM revision
                          WHERE content_uid = '$contentUid'
                          ORDER BY revision DESC
                          LIMIT $revision";

                foreach ($conn->executeQuery($query)->fetchAll() as $row) {
                    $validRevisions[] = '"'.$row['uid'].'"';
                }

                $conn->executeQuery(
                    "DELETE FROM revision
                     WHERE content_uid = '$contentUid'
                     AND uid NOT IN (".implode(', ', $validRevisions).')'
                )->execute();

                $output->writeln(
                    "    Cleaning `$contentUid` revisions done in ".(microtime(true) - $time).'s '
                    .'('.$contentCount++.'/'.count($contentUids).')'
                );
            }
        }

        $afterRevisionCount = $conn->executeQuery("SELECT count(*) as count FROM revision")->fetch();
        $afterRevisionCount = $afterRevisionCount['count'];
        $savedRows = $revisionCount - $afterRevisionCount;

        $output->writeln(
            "\nAfter cleaning, revision table contains $afterRevisionCount row(s) ($savedRows row(s) saved"
            .' - duration: '.(microtime(true) - $starttime)."s)\n"
        );
    }
}
