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

namespace BackBee\Console\Tests\Command;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Tester\CommandTester;
use BackBee\Console\Command\AclLoadCommand;
use BackBee\Console\Console;
use BackBee\Security\Group;
use BackBee\Tests\BackBeeTestCase;

/**
 * AclDbInitCommand Test.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Command\AclLoadCommand
 */
class AclLoadCommandTest extends BackBeeTestCase
{
    public function setUp()
    {
        self::$em->clear();

        self::$kernel->resetDatabase([
            self::$em->getClassMetadata('BackBee\Security\Group'),
            self::$em->getClassMetadata('BackBee\Site\Site'),
        ], true);

        self::$kernel->resetAclSchema();

        $group = new Group();
        $group->setName('Super Admin');
        self::$em->persist($group);
        self::$em->flush();
    }

    /**
     * @covers ::execute
     */
    public function testExecute()
    {
        // mock the Kernel or create one depending on your needs
        $application = new Console(self::$app);
        $application->add(new AclLoadCommand());

        $command = $application->find('acl:load');
        $commandTester = new CommandTester($command);

        vfsStream::umask(0000);
        $this->_mock_basedir = vfsStream::setup('test_dir', 0777, array(
            'file.yml' => '
groups:
  super_admin:
    sites:
      resources: all
      actions: all
            ',
        ));

        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'file' => 'vfs://test_dir/file.yml',
            )
        );

        $this->assertContains('Processing file: vfs://test_dir/file.yml', $commandTester->getDisplay());
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     * @covers ::execute
     */
    public function testExecute_invalidFile()
    {
        // mock the Kernel or create one depending on your needs
        $application = new Console(self::$app);
        $application->add(new AclLoadCommand());

        $command = $application->find('acl:load');
        $commandTester = new CommandTester($command);

        $this->_mock_basedir = vfsStream::setup('test_dir', 0777, array(
            'fileInvalid.xml' => '
incorrectly formatted
yml file',
        ));

        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'file' => 'vfs://test_dir/fileInvalid.xml',
            )
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage File not found: file_doesnt_exist.ext
     * @covers ::execute
     */
    public function testExecute_fileNotFound()
    {
        // mock the Kernel or create one depending on your needs
        $application = new Console(self::$app);
        $application->add(new AclLoadCommand());

        $command = $application->find('acl:load');
        $commandTester = new CommandTester($command);

        $file = 'file_doesnt_exist.ext';
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'file' => $file,
            )
        );
    }

    public static function tearDownAfterClass()
    {
        self::$app->resetStructure();
    }
}
