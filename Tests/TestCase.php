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

namespace BackBee\Tests;

use Doctrine\ORM\Tools\SchemaTool;
use BackBee\AutoLoader\AutoLoader;
use BackBee\Installer\EntityFinder;
use BackBee\NestedNode\Page;
use BackBee\Security\Group;
use BackBee\Security\Token\BBUserToken;
use BackBee\Security\User;
use BackBee\Site\Layout;
use BackBee\Site\Site;
use BackBee\Tests\Mock\MockBBApplication;
use BackBee\Tests\TestCase;
use BackBee\Workflow\State;

/**
 * @category    BackBee
 *
 * @copyright   Lp system
 * @author      n.dufreche
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    private $root_folder;
    private $BackBee_folder;
    private $repository_folder;
    private $mock_container = array();

    protected $bbapp;

    /**
     * Autoloader initialisation.
     */
    public function initAutoload()
    {
        $this->root_folder = self::getRootFolder();
        $this->BackBee_folder = $this->root_folder.DIRECTORY_SEPARATOR.'BackBee';
        $this->repository_folder = $this->root_folder.DIRECTORY_SEPARATOR.'repository';

        $BackBee_autoloader = new AutoLoader();

        $BackBee_autoloader->setApplication($this->getBBApp())
            ->register()
            ->registerNamespace('BackBee\ClassContent\Element', __DIR__.'/../ClassContent/Element')
        ;
    }

    public function getClassContentDir()
    {
        return array(
            $this->repository_folder.DIRECTORY_SEPARATOR.'ClassContent',
            $this->BackBee_folder.DIRECTORY_SEPARATOR.'ClassContent',
        );
    }

    /**
     * Simple load class function.
     *
     * @param type $namespace
     *
     * @throws \Exception
     */
    public function load($namespace)
    {
        try {
            $file = $this->root_folder.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $namespace).'.php';
            if (!file_exists($file)) {
                throw new \Exception('BackBeeTestUnit could not find file associeted this namespace '.$namespace);
            } else {
                include_once $file;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * get the root folder BackBee application.
     *
     * @return string
     */
    public static function getRootFolder()
    {
        return dirname(dirname(__DIR__));
    }

    /**
     * get the BackBee application folder.
     *
     * @return string
     */
    public static function getBackBeeFolder()
    {
        return realpath(self::getRootFolder().DIRECTORY_SEPARATOR.'BackBee');
    }

    /**
     * get the repository BackBee application folder.
     *
     * @return string
     */
    public static function getRepositoyFolder()
    {
        return realpath(self::getRootFolder().DIRECTORY_SEPARATOR.'repository');
    }

    /**
     * Return the mock entity corresponding at the string pass in parameter.
     *
     * @param string $obj_name the mock entity name
     *
     * @return IMock MockObject
     */
    public function getMockObjectContainer($obj_name)
    {
        if (!array_key_exists($obj_name, $this->mock_container)) {
            $class_name = '\BackBee\Tests\Mock\Mock'.ucfirst($obj_name);
            $this->mock_container[$obj_name] = new $class_name();
        }

        return $this->mock_container[$obj_name];
    }

    public function initDb($bbapp)
    {
        $em = $bbapp->getContainer()->get('em');

        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $entityFinder = new EntityFinder($bbapp->getBBDir());

        $paths = [
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
        ];

        foreach ($paths as $path) {
            $em->getConfiguration()->getMetadataDriverImpl()->addPaths([$path]);
            $em->getConfiguration()->getMetadataDriverImpl()->addExcludePaths($entityFinder->getExcludePaths($path));
        }

        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $schema = new SchemaTool($em);
        $schema->dropDatabase();
        $schema->createSchema($metadata);
    }

    public function initAcl()
    {
        $conn = $this->getBBApp()->getEntityManager()->getConnection();

        $schema = new \Symfony\Component\Security\Acl\Dbal\Schema(array(
            'class_table_name'         => 'acl_classes',
            'entry_table_name'         => 'acl_entries',
            'oid_table_name'           => 'acl_object_identities',
            'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
            'sid_table_name'           => 'acl_security_identities',
        ));

        $platform = $conn->getDatabasePlatform();

        foreach ($schema->toSql($platform) as $query) {
            $conn->executeQuery($query);
        }
    }

    /**
     * @param type $config
     *
     * @return \BackBee\BBApplication
     */
    public function getBBApp(array $config = null)
    {
        if (null === $this->bbapp) {
            $this->bbapp = new MockBBApplication(null, 'test', false, $config);
        }

        return $this->bbapp;
    }

    /**
     * Returns application.
     *
     * @param array|null $config
     *
     * @return BackBee\ApplicationInterface
     */
    public function getApplication(array $config = null)
    {
        return $this->getBBApp($config);
    }

    public function getContainer()
    {
        return $this->getBBApp()->getContainer();
    }

    /**
     * Creates a user for the specified group, and authenticates a BBUserToken.
     *
     * @param string $groupId
     *
     * @return \BackBee\Security\Token\BBUserToken
     */
    protected function createAuthUser($groupId, $roles = array('ROLE_API_USER'))
    {
        $token = new BBUserToken($roles);
        $user = new User();
        $user
            ->setEmail('admin@backbee.com')
            ->setLogin('admin')
            ->setPassword('pass')
            ->setApiKeyEnabled(true)
            ->setApiKeyPrivate(uniqid("PRIVATE", true))
            ->setApiKeyPublic(uniqid("PUBLIC", true))
        ;

        $group = $this->getBBApp()->getEntityManager()
            ->getRepository('BackBee\Security\Group')
            ->findOneBy(array('_name' => $groupId))
        ;

        if (!$group) {
            $group = new Group();
            $group->setName($groupId);
            $this->getBBApp()->getEntityManager()->persist($group);
            $this->getBBApp()->getEntityManager()->flush($group);
        }

        $user->addGroup($group);

        $token->setUser($user);
        $token->setAuthenticated(true);

        $this->getSecurityContext()->setToken($token);

        return $user;
    }

    /**
     * @return \BackBee\Security\SecurityContext
     */
    protected function getSecurityContext()
    {
        return $this->getBBApp()->getSecurityContext();
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->getBBApp()->getEntityManager($name);
    }

    /**
     * @return \BackBee\Security\Acl\AclManager
     */
    protected function getAclManager()
    {
        return $this->getBBApp()->getContainer()->get('security.acl_manager');
    }

    public function dropDb($bbapp)
    {
        $em = $bbapp->getContainer()->get('em');
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($em);
        $schema->dropSchema($metadata);
    }

    public function createRootPage()
    {
        $page = new Page('test', array('title' => 'title', 'url' => 'url'));

        $layout = new Layout();
        $page->setLayout($layout->setDataObject($this->getDefaultLayoutZones()));

        return $page;
    }

    public function createPage()
    {
        $page = $this->createRootPage();

        $page->setParent($this->createRootPage());

        return $page;
    }

    /**
     * Builds a default set of layout zones.
     *
     * @return \stdClass
     */
    protected function getDefaultLayoutZones()
    {
        $mainzone = new \stdClass();
        $mainzone->id = 'main';
        $mainzone->defaultContainer = null;
        $mainzone->target = '#target';
        $mainzone->gridClassPrefix = 'row';
        $mainzone->gridSize = 8;
        $mainzone->mainZone = true;
        $mainzone->defaultClassContent = 'ContentSet';
        $mainzone->options = null;

        $asidezone = new \stdClass();
        $asidezone->id = 'aside';
        $asidezone->defaultContainer = null;
        $asidezone->target = '#target';
        $asidezone->gridClassPrefix = 'row';
        $asidezone->gridSize = 4;
        $asidezone->mainZone = false;
        $asidezone->defaultClassContent = 'inherited';
        $asidezone->options = null;

        $data = new \stdClass();
        $data->templateLayouts = array(
            $mainzone,
            $asidezone,
        );

        return $data;
    }
}
