<?php

namespace BackBee\Tests;

use BackBee\Installer\EntityFinder;
use BackBee\NestedNode\Page;
use BackBee\Security\Token\BBUserToken;
use BackBee\Security\Group;
use BackBee\Security\User;
use BackBee\Site\Layout;
use BackBee\Tests\Mock\MockBBApplication;

use Doctrine\ORM\Tools\SchemaTool;

use Symfony\Component\Security\Acl\Dbal\Schema;

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
final class TestKernel
{
    /**
     * Singleton pattern, unique instance of TestKernel.
     *
     * @var TestKernel
     */
    private static $instance;

    /**
     * @var MockBBApplication
     */
    private $app;

    /**
     * Returns the unique instance of TestKernel.
     *
     * @return TestKernel
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new TestKernel();
        }

        return self::$instance;
    }

    /**
     * Returns TestKernel unique MockBBApplication instance.
     *
     * @return MockBBApplication
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * Returns TestKernel unique application's EntityManager instance.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->app->getEntityManager();
    }

    /**
     * Reset partially or completely the database.
     *
     * @param  array|null $entityMetadata The array that contains only metadata of entities we want to create
     * @param  boolean    $hardReset      This option force hard reset of the entire database
     * @return self
     */
    public function resetDatabase(array $entityMetadata = null, $hardReset = false)
    {
        $schemaTool = new SchemaTool($this->getEntityManager());

        if (null === $entityMetadata || true === $hardReset) {
            $schemaTool->dropDatabase();
        } else {
            $schemaTool->dropSchema($entityMetadata);
        }

        if (null === $entityMetadata) {
            $entityFinder = new EntityFinder($this->getApplication()->getBBDir());

            $metadataDriver = $this->getEntityManager()->getConfiguration()->getMetadataDriverImpl();
            foreach ($this->getEntityPaths() as $path) {
                $metadataDriver->addPaths([$path]);
                $metadataDriver->addExcludePaths($entityFinder->getExcludePaths($path));
            }

            $entityMetadata = $metadata = $this->getEntityManager()->getMetadataFactory()->getAllMetadata();
            $this->getEntityManager()
                ->getClassMetadata('BackBee\ClassContent\AbstractClassContent')
                ->addDiscriminatorMapClass(
                    'BackBee\ClassContent\Tests\Mock\MockContent',
                    'BackBee\ClassContent\Tests\Mock\MockContent'
                )
            ;
        }

        $schemaTool->createSchema($entityMetadata);

        return $this;
    }

    public function resetAclSchema()
    {
        $conn = $this->getEntityManager()->getConnection();

        $tablesMapping = [
            'class_table_name'         => 'acl_classes',
            'entry_table_name'         => 'acl_entries',
            'oid_table_name'           => 'acl_object_identities',
            'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
            'sid_table_name'           => 'acl_security_identities',
        ];

        foreach ($tablesMapping as $tableName) {
            $conn->executeQuery(sprintf('DROP TABLE IF EXISTS %s', $tableName));
        }

        $schema = new Schema($tablesMapping);

        $platform = $conn->getDatabasePlatform();

        foreach ($schema->toSql($platform) as $query) {
            $conn->executeQuery($query);
        }

        return $this;
    }

    public function createRootPage($uid = null)
    {
        $page = new Page($uid, [
            'title' => 'title',
            'url'   => 'url',
        ]);

        $page->setLayout($this->createLayout('test'));

        return $page;
    }

    public function createPage($uid = null)
    {
        $page = $this->createRootPage($uid);
        return $page;
    }

    public function createLayout($label, $uid = null)
    {
        $layout = new Layout($uid);
        $layout->setLabel($label);
        $layout->setPath('/'.$label);
        $layout->setDataObject($this->getDefaultLayoutZones());

        return $layout;
    }

    /**
     * Builds a default set of layout zones.
     *
     * @return \stdClass
     */
    public function getDefaultLayoutZones()
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
        $data->templateLayouts = [
            $mainzone,
            $asidezone,
        ];

        return $data;
    }

    /**
     * Creates a user for the specified group and authenticates a BBUserToken with the newly created user.
     *
     * Note that the token is setted into application security context.
     *
     * @param string $groupId
     * @return User
     */
    public function createAuthenticatedUser($groupId, array $roles = ['ROLE_API_USER'])
    {
        $em    = $this->getEntityManager();
        $group = $em->getRepository('BackBee\Security\Group')->findOneBy([
            '_name' => $groupId,
        ]);

        if (null === $group) {
            $group = new Group();
            $group->setName($groupId);
            $em->persist($group);
            $em->flush($group);
        }

        $user = new User();
        $user
            ->setEmail('admin@backbee.com')
            ->setLogin('admin')
            ->setPassword('pass')
            ->setApiKeyPrivate(uniqid('PRIVATE', true))
            ->setApiKeyPublic(uniqid('PUBLIC', true))
            ->setApiKeyEnabled(true)
            ->addGroup($group)
        ;

        $token = new BBUserToken($roles);
        $token->setAuthenticated(true);
        $token
            ->setUser($user)
            ->setCreated(new \DateTime())
            ->setLifetime(300)
        ;

        $this->app->getSecurityContext()->setToken($token);

        return $user;
    }

    /**
     * Creates an instance of TestKernel and initialize application's autoloader.
     */
    private function __construct()
    {
        $this->app = new MockBBApplication(null, 'test');

        $this->app->getAutoloader()
            ->register()
            ->registerNamespace('BackBee\ClassContent\Element', $this->buildPath([
                $this->getApplication()->getBBDir(),
                'ClassContent',
                'Element',
            ]))
        ;
    }

    /**
     * Returns array that contains every application entity path.
     *
     * @return array
     */
    private function getEntityPaths()
    {
        $bbDir = $this->getApplication()->getBBDir();

        return [
            $this->buildPath([$bbDir, 'Bundle']),
            $this->buildPath([$bbDir, 'Cache', 'DAO']),
            $this->buildPath([$bbDir, 'ClassContent']),
            $this->buildPath([$bbDir, 'ClassContent', 'Indexes']),
            $this->buildPath([$bbDir, 'Logging']),
            $this->buildPath([$bbDir, 'NestedNode']),
            $this->buildPath([$bbDir, 'Security']),
            $this->buildPath([$bbDir, 'Site']),
            $this->buildPath([$bbDir, 'Stream', 'ClassWrapper']),
            $this->buildPath([$bbDir, 'Util', 'Sequence', 'Entity']),
            $this->buildPath([$bbDir, 'Workflow']),
        ];
    }

    /**
     * Builds path with provided array by linking every piece with directory seperator.
     *
     * @param  array  $pieces The final path pieces
     * @return string
     */
    private function buildPath(array $pieces)
    {
        return implode(DIRECTORY_SEPARATOR, $pieces);
    }
}
