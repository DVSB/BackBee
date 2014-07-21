<?php
namespace BackBuilder\Tests;

use BackBuilder\AutoLoader\AutoLoader;

use org\bovigo\vfs\vfsStream;

use Doctrine\ORM\Tools\SchemaTool,
    Doctrine\ORM\EntityManager;

use BackBuilder\Tests\Mock\MockBBApplication;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Tests
 * @copyright   Lp system
 * @author      n.dufreche
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    private $root_folder;
    private $backbuilder_folder;
    private $repository_folder;
    private $mock_container = array();
    
    protected $bbapp;

    /**
     * Autoloader initialisation
     */
    public function initAutoload()
    {
        $this->root_folder = self::getRootFolder();
        $this->backbuilder_folder = $this->root_folder . DIRECTORY_SEPARATOR . 'BackBuilder';
        $this->repository_folder = $this->root_folder . DIRECTORY_SEPARATOR . 'repository';

        $backbuilder_autoloader = new AutoLoader();

        $backbuilder_autoloader->register()
                ->registerNamespace('BackBuilder\Bundle\Tests', implode(DIRECTORY_SEPARATOR, array($this->root_folder, 'bundle', 'Tests')))
                ->registerNamespace('BackBuilder\Bundle', implode(DIRECTORY_SEPARATOR, array($this->root_folder, 'bundle')))
                ->registerNamespace('BackBuilder\Tests\Fixtures', implode(DIRECTORY_SEPARATOR, array($this->repository_folder, 'Fixtures')))
                ->registerNamespace('BackBuilder\ClassContent\Repository', implode(DIRECTORY_SEPARATOR, array($this->repository_folder, 'ClassContent', 'Repositories')))
                ->registerNamespace('BackBuilder\Renderer\Helper', implode(DIRECTORY_SEPARATOR, array($this->repository_folder, 'Templates', 'helpers')))
                ->registerNamespace('BackBuilder\Event\Listener', implode(DIRECTORY_SEPARATOR, array($this->repository_folder, 'Listeners')))
                ->registerNamespace('BackBuilder\Services\Public', implode(DIRECTORY_SEPARATOR, array($this->repository_folder, 'Services', 'Public')))
                ->registerNamespace('Doctrine\Tests', implode(DIRECTORY_SEPARATOR, array($this->root_folder, 'vendor', 'doctrine', 'orm', 'tests', 'Doctrine', 'Tests')));
    }

    /**
     * Simple load class function
     *
     * @param type $namespace
     * @throws \Exception
     */
    public function load($namespace) {
        try {
            if (!file_exists($this->root_folder.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $namespace).'.php')) {
                throw new \Exception('BackBuilderTestUnit could not find file associeted this namespace '.$namespace);
            } else {
                include_once $this->root_folder.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $namespace).'.php';
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * get the root folder BackBuilder application
     *
     * @return string
     */
    public static function getRootFolder()
    {
        return dirname(dirname(__DIR__));
    }

    /**
     * get the BackBuilder application folder
     *
     * @return string
     */
    public static function getBackbuilderFolder()
    {
        return realpath(self::getRootFolder() . DIRECTORY_SEPARATOR . 'BackBuilder');
    }

    /**
     * get the repository BackBuilder application folder
     *
     * @return string
     */
    public static function getRepositoyFolder()
    {
        return realpath(self::getRootFolder() . DIRECTORY_SEPARATOR . 'repository');;
    }

    /**
     * Return the mock entity corresponding at the string pass in parameter
     *
     * @param string $obj_name the mock entity name
     * @return IMock MockObject
     */
    public function getMockObjectContainer($obj_name)
    {
        if(!array_key_exists($obj_name, $this->mock_container)) {
            $class_name = '\BackBuilder\Tests\Mock\Mock'.ucfirst($obj_name);
            $this->mock_container[$obj_name] = new $class_name();
        }
        return $this->mock_container[$obj_name];
    }

    /**
     * get the BBApplication stub
     *
     * return BackBuilder\BBAplication
     */
    public function getBBAppStub()
    {
        $BBApp = $this->getMockBuilder('BackBuilder\BBApplication')->disableOriginalConstructor()->getMock();
        $BBApp->expects($this->any())
              ->method('getRenderer')
              ->will($this->returnValue($this->getMockObjectContainer('renderer')));

        $BBApp->expects($this->any())
              ->method('getAutoloader')
              ->will($this->returnValue($this->getMockObjectContainer('autoloader')));

        $BBApp->expects($this->any())
              ->method('getSite')
              ->will($this->returnValue($this->getMockObjectContainer('site')));

        $BBApp->expects($this->any())
              ->method('getConfig')
              ->will($this->returnValue($this->getMockObjectContainer('config')));

        $BBApp->expects($this->any())
              ->method('getEntityManager')
              ->will($this->returnValue($this->getMockObjectContainer('entityManager')));

        $BBApp->expects($this->any())
              ->method('getEventDispatcher')
              ->will($this->returnValue(new \BackBuilder\Tests\Mock\EventDispatcher\MockNoopEventDispatcher($BBApp)));


        $BBApp->expects($this->any())
              ->method('getContainer')
              ->will($this->returnValue(new \BackBuilder\Tests\Mock\EventDispatcher\MockNoopEventDispatcher($BBApp)));


        $BBApp->expects($this->any())
              ->method('getBaseDir')
              ->will($this->returnValue(vfsStream::url('')));

//        $controller = $this->getMockBuilder('BackBuilder\FrontController\FrontController')
//                ->setConstructorArgs(array($BBApp))
//                ->setMethods(array())
//                ->getMock();
        $controller = new \BackBuilder\FrontController\FrontController($BBApp);

        $BBApp->expects($this->any())
              ->method('getController')
              ->will($this->returnValue($controller));

        return $BBApp;
    }


    public function initDb($bbapp)
    {
        $em = $bbapp->getContainer()->get('em');

        $em->getConfiguration()->getMetadataDriverImpl()->addPaths(array(
            $bbapp->getBBDir() . '/Bundle',
            $bbapp->getBBDir() . '/Cache/DAO',
            // the following 2 classes are throwing an exception: index IDX_CLASSNAME already exists
//            $bbapp->getBBDir() . '/ClassContent',
//            $bbapp->getBBDir() . '/ClassContent/Indexes',
            $bbapp->getBBDir() . '/Logging',
            $bbapp->getBBDir() . '/NestedNode',
            $bbapp->getBBDir() . '/Security',
            $bbapp->getBBDir() . '/Site',
            $bbapp->getBBDir() . '/Site/Metadata',
            $bbapp->getBBDir() . '/Stream/ClassWrapper',
            $bbapp->getBBDir() . '/Theme',
            $bbapp->getBBDir() . '/Util/Sequence/Entity',
            $bbapp->getBBDir() . '/Workflow',
        ));


        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($em);
        $schema->updateSchema($metadata, true);
    }

    public function dropDb($bbapp)
    {
        $em = $this->getBBApp()->getContainer()->get('em');

        $em->getConfiguration()->getMetadataDriverImpl()->addPaths(array(
            $bbapp->getBBDir() . '/Bundle',
            $bbapp->getBBDir() . '/Cache/DAO',
            $bbapp->getBBDir() . '/ClassContent',
            $bbapp->getBBDir() . '/ClassContent/Indexes',
            $bbapp->getBBDir() . '/Logging',
            $bbapp->getBBDir() . '/NestedNode',
            $bbapp->getBBDir() . '/Security',
            $bbapp->getBBDir() . '/Site',
            $bbapp->getBBDir() . '/Site/Metadata',
            $bbapp->getBBDir() . '/Stream/ClassWrapper',
            $bbapp->getBBDir() . '/Theme',
            $bbapp->getBBDir() . '/Util/Sequence/Entity',
            $bbapp->getBBDir() . '/Workflow',
        ));

        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($em);
        $schema->dropDatabase();
    }
    
    /**
     * 
     * @return \BackBuilder\BBApplication
     */
    public function getBBApp()
    {
        if(null === $this->bbapp) {
            //$this->bbapp = new \BackBuilder\BBApplication(null, 'test');
            
            
            $mockConfig = array(
                'cache' => array(
                    'default' => array()
                ),
                'log' => array(),
                'repository' => array(
                    'ClassContent' => array(),
                    'Config' => array(
                        'config.yml' => file_get_contents(__DIR__ . '/config/config.yml'),
                        'doctrine.yml' => file_get_contents(__DIR__ . '/config/doctrine.yml'),
                        'logging.yml' => file_get_contents(__DIR__ . '/config/logging.yml'),
                        'bootstrap.yml' => file_get_contents(__DIR__ . '/config/bootstrap.yml'),
                    ),
                    'Data' => array(
                        'Media' => array(),
                        'Storage' => array(),
                        'Tmp' => array()
                    ),
                    'Ressources' => array()
                )
            );
            
            $this->bbapp = new MockBBApplication(null, $mockConfig);
        }
        
        return $this->bbapp;
    }

}