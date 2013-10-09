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
            if (is_dir($content)) {
                $tmp = $this->_getEntities($path . DIRECTORY_SEPARATOR . $content);
                array_merge($entities, $tmp);
            } else {
                $className = $this->_getClassName($content);
                if ($this->_isEntity($className)) {
                    $entities[] = $className; 
                }
            }
        }
        return $entities;
    }
    
    private function _isEntity($className)
    {
        $annotation = $this->_getEntityAnnotation(new \ReflectionClass($className));
        var_dump($annotation);
        return $annotation;
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