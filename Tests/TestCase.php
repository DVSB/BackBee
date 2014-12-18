<?php
namespace BackBee\Tests;

use Doctrine\ORM\Tools\SchemaTool;
use BackBee\AutoLoader\AutoLoader;
use BackBee\Installer\EntityFinder;
use BackBee\Security\Token\BBUserToken;
use BackBee\Security\User;
use BackBee\Security\Group;
use BackBee\Tests\Mock\MockBBApplication;

/**
 * @category    BackBee
 * @package     BackBee\Tests
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
     * Autoloader initialisation
     */
    public function initAutoload()
    {
        $this->root_folder = self::getRootFolder();
        $this->BackBee_folder = $this->root_folder.DIRECTORY_SEPARATOR.'BackBee';
        $this->repository_folder = $this->root_folder.DIRECTORY_SEPARATOR.'repository';

        $BackBee_autoloader = new AutoLoader();

        $BackBee_autoloader->setApplication($this->getBBApp())
                ->register()
                ->registerNamespace('BackBee\Bundle\Tests', implode(DIRECTORY_SEPARATOR, array($this->root_folder, 'bundle', 'Tests')))
                ->registerNamespace('BackBee\Bundle', implode(DIRECTORY_SEPARATOR, array($this->root_folder, 'bundle')))
                ->registerNamespace('BackBee\Tests\Fixtures', implode(DIRECTORY_SEPARATOR, array($this->repository_folder, 'Fixtures')))
                ->registerNamespace('BackBee\ClassContent\Repository', implode(DIRECTORY_SEPARATOR, array($this->repository_folder, 'ClassContent', 'Repositories')))
                ->registerNamespace('BackBee\Renderer\Helper', implode(DIRECTORY_SEPARATOR, array($this->repository_folder, 'Templates', 'helpers')))
                ->registerNamespace('BackBee\Event\Listener', implode(DIRECTORY_SEPARATOR, array($this->repository_folder, 'Listeners')))
                ->registerNamespace('BackBee\Services\Public', implode(DIRECTORY_SEPARATOR, array($this->repository_folder, 'Services', 'Public')))
                ->registerNamespace('Doctrine\Tests', implode(DIRECTORY_SEPARATOR, array($this->root_folder, 'vendor', 'doctrine', 'orm', 'tests', 'Doctrine', 'Tests')));
    }

    public function getClassContentDir()
    {
        return array(
            $this->repository_folder.DIRECTORY_SEPARATOR.'ClassContent',
            $this->BackBee_folder.DIRECTORY_SEPARATOR.'ClassContent',
        );
    }

    /**
     * Simple load class function
     *
     * @param  type       $namespace
     * @throws \Exception
     */
    public function load($namespace)
    {
        try {
            if (!file_exists($this->root_folder.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $namespace).'.php')) {
                throw new \Exception('BackBeeTestUnit could not find file associeted this namespace '.$namespace);
            } else {
                include_once $this->root_folder.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $namespace).'.php';
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * get the root folder BackBee application
     *
     * @return string
     */
    public static function getRootFolder()
    {
        return dirname(dirname(__DIR__));
    }

    /**
     * get the BackBee application folder
     *
     * @return string
     */
    public static function getBackBeeFolder()
    {
        return realpath(self::getRootFolder().DIRECTORY_SEPARATOR.'BackBee');
    }

    /**
     * get the repository BackBee application folder
     *
     * @return string
     */
    public static function getRepositoyFolder()
    {
        return realpath(self::getRootFolder().DIRECTORY_SEPARATOR.'repository');
    }

    /**
     * Return the mock entity corresponding at the string pass in parameter
     *
     * @param  string $obj_name the mock entity name
     * @return IMock  MockObject
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
     *
     * @param  type                       $config
     * @return \BackBee\BBApplication
     */
    public function getBBApp(array $config = null)
    {
        if (null === $this->bbapp) {
            $this->bbapp = new MockBBApplication(null, 'test', false, $config);
        }

        return $this->bbapp;
    }

    public function getContainer()
    {
        return $this->getBBApp()->getContainer();
    }

    /**
     * Creates a user for the specified group, and authenticates a BBUserToken
     * @param  string                                  $groupId
     * @return \BackBee\Security\Token\BBUserToken
     */
    protected function createAuthUser($groupId, $roles = array())
    {
        $token = new BBUserToken($roles);
        $user = new User();
        $user
            ->setLogin(uniqid('login'))
            ->setPassword('pass')
            ->setApiKeyEnabled(true)
            ->setApiKeyPrivate(uniqid("PRIVATE", true))
            ->setApiKeyPublic(uniqid("PUBLIC", true))
        ;

        $group = $this->getBBApp()->getEntityManager()
            ->getRepository('BackBee\Security\Group')
            ->findOneBy(array('_identifier' => $groupId))
        ;

        if (!$group) {
            $group = new Group();
            $group->setIdentifier($groupId);
            $group->setName($groupId);
        }

        $user->addGroup($group);

        $token->setUser($user);
        $token->setAuthenticated(true);

        $this->getSecurityContext()->setToken($token);

        return $token;
    }

    /**
     *
     * @return \BackBee\Security\SecurityContext
     */
    protected function getSecurityContext()
    {
        return $this->getBBApp()->getSecurityContext();
    }

    /**
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->getBBApp()->getEntityManager($name);
    }

    /**
     *
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
}
