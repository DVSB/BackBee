<?php
namespace BackBuilder\Installer;

use BackBuilder\BBApplication,
    BackBuilder\Util\Dir;

use Doctrine\ORM\Mapping\Entity,
    Doctrine\Common\Annotations\SimpleAnnotationReader;

class Database
{
    /**
     * @var \Doctrine\ORM\EntityManager 
     */
    private $_em;
    private $_application;
    private $_annotationReader;


    public function __construct(BBApplication $application)
    {
        $this->_application = $application;
        $this->_em = $application->getEntityManager();
    }
    
    public function buildBackbuilderSchema()
    {
        $entities = $this->_getEntities();
        var_dump($entities);
    }

    private function _getEntities($path = null)
    {
        $entities = array();
        if (is_null($path)) {
            $path = $this->_application->getBBDir();
        }
        foreach (Dir::getContent($path) as $content) {
            $subpath = $path . DIRECTORY_SEPARATOR . $content;
            if ($content == 'Resources') continue;
            if (is_dir($subpath)) {
                $entities = array_merge($entities, $this->_getEntities($subpath));
            } else {
                if (strpos($subpath, '.php')) {
                    $namespace = $this->getNamespace($subpath);
                    var_dump($namespace);
                    $reflection = new \ReflectionClass($namespace);
                    if ($this->_isEntity($reflection)) {
                        $entities[] = $namespace; 
                    }
                }
            }
        }
        return $entities;
    }
    
    private function getNamespace($file)
    {
        return str_replace(array($this->_application->getBBDir(), '.php', '/'), array('BackBuilder', '', '\\'), $file);
    }

    private function _isEntity(\ReflectionClass $reflection)
    {
        return !is_null($this->_getEntityAnnotation($reflection));
    }
    
    private function _getEntityAnnotation($class)
    {
        if (!$this->_annotationReader) {
            $this->_annotationReader = new SimpleAnnotationReader();
            $this->_annotationReader->addNamespace('Doctrine\ORM\Mapping');
        }
        return $this->_annotationReader->getClassAnnotation($class, new Entity());
    }
}