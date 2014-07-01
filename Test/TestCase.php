<?php
namespace BackBuilder\Test;

use BackBuilder\AutoLoader\AutoLoader;

use org\bovigo\vfs\vfsStream;

/**
 * @category    BackBuilder
 * @package     BackBuilder\TestUnit
 * @copyright   Lp system
 * @author      n.dufreche
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    private $root_folder;
    private $backbuilder_folder;
    private $repository_folder;
    private $mock_container = array();

    /**
     * Autoloader initialisation
     */
    public function initAutoload()
    {
        $this->root_folder = dirname(__DIR__);
        $this->backbuilder_folder = $this->root_folder . DIRECTORY_SEPARATOR . 'BackBuilder';
        $this->repository_folder = $this->root_folder . DIRECTORY_SEPARATOR . 'repository';

        $this->load('BackBuilder\AutoLoader\AutoLoader');
        $backbuilder_autoloader = new AutoLoader();

        $backbuilder_autoloader->register()
                ->registerNamespace('BackBuilder\TestUnit', __DIR__)
                ->registerNamespace('BackBuilder\Bundle\TestUnit', implode(DIRECTORY_SEPARATOR, array($this->root_folder, 'bundle', 'Test')))
                ->registerNamespace('BackBuilder\Bundle', implode(DIRECTORY_SEPARATOR, array($this->root_folder, 'bundle')))
                ->registerNamespace('BackBuilder\TestUnit\Fixtures', implode(DIRECTORY_SEPARATOR, array($this->repository_folder, 'Fixtures')))
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
        return dirname(__DIR__);
    }

    /**
     * get the BackBuilder application folder
     *
     * @return string
     */
    public static function getBackbuilderFolder()
    {
        return realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'BackBuilder');
    }

    /**
     * get the repository BackBuilder application folder
     *
     * @return string
     */
    public static function getRepositoyFolder()
    {
        return realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'repository');;
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
            $class_name = '\BackBuilder\TestUnit\Mock\Mock'.ucfirst($obj_name);
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
              ->method('getBaseDir')
              ->will($this->returnValue(vfsStream::url('')));

        return $BBApp;
    }
}