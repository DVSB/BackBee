<?php
namespace BackBuilder\Installer;

use BackBuilder\BBApplication,
    BackBuilder\Util\Dir;

use Doctrine\ORM\Mapping\Entity,
    Doctrine\Common\Annotations\SimpleAnnotationReader,
    Doctrine\ORM\Tools\SchemaTool;

class Database
{
    /**
     * @var \Doctrine\ORM\EntityManager 
     */
    private $_em;
    /**
     * @var BBApplication 
     */
    private $_application;
    /**
     * @var SimpleAnnotationReader 
     */
    private $_annotationReader;
    /**
     * @var SchemaTool 
     */
    private $_schemaTool;

    /**
     * @param \BackBuilder\BBApplication $application
     */
    public function __construct(BBApplication $application)
    {
        $this->_application = $application;
        $this->_em = $this->_application->getEntityManager();
        $this->_schemaTool = new SchemaTool($this->_em);
    }
    
    /**
     * Create the BackBuilder schema
     */
    public function createBackbuilderSchema()
    {
        $classes = $this->_getBackbuilderSchema();
        $this->_schemaTool->dropSchema($classes);
        $this->_schemaTool->createSchema($classes);
    }

    /**
     * create all bundles schema.
     */
    public function createBundlesSchema()
    {
        $classes = $this->_getBundlesClasses();
        $this->_schemaTool->dropSchema($classes);
        $this->_schemaTool->createSchema($classes);
    }

    /**
     * Create the bundle schema specified in param
     * 
     * @param string $bundleName
     */
    public function createBundleSchema($bundleName)
    {
        $classes = $this->_getBundleSchema($this->_application->getBundle($bundleName));
        $this->_schemaTool->dropSchema($classes);
        $this->_schemaTool->createSchema($classes);
    }

     /**
     * update backbuilder schema.
     */
    public function updateBackbuilderSchema()
    {
        $this->_schemaTool->updateSchema($this->_getBackbuilderSchema());
    }
    
    /**
     * update all bundles schema.
     */
    public function updateBundlesSchema()
    {
        $this->_schemaTool->updateSchema($this->_getBundlesClasses());
    }

    /**
     * update the bundle schema specified in param
     * 
     * @param string $bundleName
     */
    public function updateBundleSchema($bundleName)
    {
        $classes = $this->_getBundleSchema($this->_application->getBundle($bundleName));
        $this->_schemaTool->updateSchema($classes);
    }

    /**
     * @return array
     */
    private function _getBundlesClasses()
    {
        $classes = array();
        foreach ($this->_application->getBundles() as $bundle) {
            $tmp = $this->_getBundleSchema($bundle);
            $classes = array_merge($classes, $tmp);
        }
        return $classes;
    }
    
    /**
     * @return array
     */
    private function _getBackbuilderSchema()
    {
        $classes = array();
        foreach ($this->_getEntities($this->_application->getBBDir()) as $className) {
            $classes[] = $this->_em->getClassMetadata($className);
        }
        return $classes;
    }

    /**
     * @param \BackBuilder\Bundle\ABundle $bundle
     * @return array
     */
    private function _getBundleSchema($bundle)
    {
        $reflection = new \ReflectionClass(get_class($bundle));
        $classes = array();
        foreach ($this->_getEntities(dirname($reflection->getFileName())) as $className) {
            $classes[] = $this->_em->getClassMetadata($className);
        }
        return $classes;
    }

    /**
     * @param string $path
     * @return array
     */
    private function _getEntities($path)
    {
        $entities = array();
        foreach (Dir::getContent($path) as $content) {
            $subpath = $path . DIRECTORY_SEPARATOR . $content;
            if ($content == 'Resources' || $content == 'Ressources' || $content == 'TestUnit') continue;
            if (is_dir($subpath)) {
                $entities = array_merge($entities, $this->_getEntities($subpath));
            } else {
                if (strpos($subpath, '.php')) {
                    $namespace = $this->getNamespace($subpath);
                    if ($this->_isEntity(new \ReflectionClass($namespace))) {
                        $entities[] = $namespace; 
                    }
                }
            }
        }
        return $entities;
    }
    
    /**
     * @param string $file
     * @return string
     */
    private function getNamespace($file)
    {
        $classname = str_replace(array($this->_application->getBaseDir(), 'bundle', '.php', '/'), array('', 'Bundle', '', '\\'), $file);
        return strpos('BackBuilder', $classname) ? $classname : 'BackBuilder' . $classname;
    }

    /**
     * @param \ReflectionClass $reflection
     * @return boolean
     */
    private function _isEntity(\ReflectionClass $reflection)
    {
        return !is_null($this->_getEntityAnnotation($reflection));
    }
    
    /**
     * @param \ReflectionClass $class
     * @return Entity
     */
    private function _getEntityAnnotation(\ReflectionClass $class)
    {
        if (!$this->_annotationReader) {
            $this->_annotationReader = new SimpleAnnotationReader();
            $this->_annotationReader->addNamespace('Doctrine\ORM\Mapping');
        }
        return $this->_annotationReader->getClassAnnotation($class, new Entity());
    }
}