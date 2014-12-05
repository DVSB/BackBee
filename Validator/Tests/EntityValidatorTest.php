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

namespace BackBuilder\Validator\Tests;

use BackBuilder\Tests\Mock\MockBBApplication;
use BackBuilder\Validator\Tests\Mock\MockEntity;
use BackBuilder\Validator\Tests\Mock\MockEntity2;

/**
 * Entity's validator
 *
 * @category    BackBuilder
 * @package     BackBuilder\Validator\Tests
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class EntityValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $entity_validator;
    private $application;
    private $em;

    /**
     * @covers BackBuilder\Validator\EntityValidator::doUniqueValidator
     */
    public function testDoUniqueValidator()
    {
        $config = array('error' => 'Value must be unique.');
        $mock_entity = $this->em->getRepository('BackBuilder\Validator\Tests\Mock\MockEntity')->find(1);

        $errors = array();
        $this->entity_validator->doUniqueValidator($mock_entity, $errors, 'name', 'France', $config);
        $this->assertTrue(array_key_exists('name', $errors));

        $errors = array();
        $this->entity_validator->doUniqueValidator($mock_entity, $errors, 'name', 'Brazil', $config);
        $this->assertFalse(array_key_exists('name', $errors));
    }

    /**
     * @covers BackBuilder\Validator\EntityValidator::doPasswordValidator
     */
    public function testDoPasswordValidator()
    {
        $datas = array('conf-password' => 'foo');
        $config = array('error' => 'Password must be equal with confirm password');

        $errors = array();
        $this->entity_validator->doPasswordValidator($errors, 'password', 'foo', $datas, $config);
        $this->assertFalse(array_key_exists('password', $errors));

        $errors = array();
        $this->entity_validator->doPasswordValidator($errors, 'password', 'bar', $datas, $config);
        $this->assertTrue(array_key_exists('password', $errors));
    }

    /*
     * @covers BackBuilder\Validator\EntityValidator::isValid
     */
    public function testIsValid()
    {
        $this->assertTrue($this->entity_validator->isValid(new MockEntity(), array('error' => 'An Error occured.')));
        $this->assertFalse($this->entity_validator->isValid(array('foo'), array('error' => 'An Error occured.')));
        $this->assertFalse($this->entity_validator->isValid(new MockEntity(), array()));
        $this->assertFalse($this->entity_validator->isValid(new MockEntity(), null));
        $this->assertFalse($this->entity_validator->isValid(null, null));
    }

    /**
     * @covers BackBuilder\Validator\EntityValidator::getReflectionClass
     */
    public function testGetReflectionClass()
    {
        $this->assertInstanceOf('ReflectionClass', $this->entity_validator->getReflectionClass(new MockEntity()));
    }

    /**
     * @covers BackBuilder\Validator\ArrayValidator::getReflectionClass
     * @expectedException InvalidArgumentException
     */
    public function testGetReflectionClassIfEntityIsAnArray()
    {
        $this->entity_validator->getReflectionClass(array());
    }

    /**
     * @covers BackBuilder\Validator\ArrayValidator::getReflectionClass
     * @expectedException InvalidArgumentException
     */
    public function testGetReflectionClassIfEntityIsNull()
    {
        $this->entity_validator->getReflectionClass(null);
    }

    /**
     * @covers BackBuilder\Validator\EntityValidator::getReflectionClass
     */
    public function testGetIdProperties()
    {
        $this->assertEquals(array('id'), $this->entity_validator->getIdProperties(new MockEntity()));
        $this->assertEquals(array('id', 'id2'), $this->entity_validator->getIdProperties(new MockEntity2(4, 5)));
    }

    /**
     * Sets up the fixture
     */
    public function setUp()
    {
        //Generate schema
        $this->generateSchema();
        //Test data
        $this->generateData();

        $this->entity_validator = new \BackBuilder\Validator\EntityValidator($this->em);
    }

    private function generateData()
    {
        $entity1 = new MockEntity();
        $entity1->setName('France')
                ->setNumericCode('10');
        $this->em->persist($entity1);

        $entity2 = new MockEntity();
        $entity2->setName('England')
                ->setNumericCode('11');
        $this->em->persist($entity2);

        $entity3 = new MockEntity();
        $entity3->setName('France')
                ->setNumericCode('13');
        $this->em->persist($entity3);

        $this->em->flush();
    }

    private function generateSchema()
    {
        $bootstrap_yml = array(
            'debug'     => true,
            'container' => array(
                'dump_directory' => '',
                'autogenerate'   => true,
            ),
        );

        $mockConfig = array(
            'cache' => array(
                'default' => array(),
            ),
            'log' => array(),
            'repository' => array(
                'ClassContent' => array(),
                'Config' => array(
                    'config.yml'    => file_get_contents(__DIR__.'/config.yml'),
                    'bootstrap.yml' => \Symfony\Component\Yaml\Yaml::dump($bootstrap_yml),
                ),
                'Data' => array(
                    'Media' => array(),
                    'Storage' => array(),
                    'Tmp' => array(),
                ),
                'Ressources' => array(),
            ),
        );

        $this->application = new MockBBApplication(null, $mockConfig);
        $this->em = $this->application->getEntityManager();

        $st = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $st->createSchema(array($this->em->getClassMetaData('BackBuilder\Validator\Tests\Mock\MockEntity')));
        $st->createSchema(array($this->em->getClassMetaData('BackBuilder\Validator\Tests\Mock\MockEntity2')));
    }
}
