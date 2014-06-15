<?php

namespace BackBuilder\Installer;

use BackBuilder\Util\Dir;
use Doctrine\Common\Annotations\SimpleAnnotationReader,
    Doctrine\ORM\Mapping\Entity;

class EntityFinder
{

    /**
     * @var array
     */
    private $_ignoredFolder = array(
        '/Resources/',
        '/Ressources/',
        '/Test/',
        '/TestUnit/',
        '/Exception/',
        '/Commands/',
        '/Installer/',
        '/Assets/',
        '/Renderer/',
        '/Templates/',
        '/helpers/',
    );


    /**
     * @var string
     */
    private $_baseDir;

    /**
     * @var SimpleAnnotationReader
     */
    private $_annotationReader;

    public function __construct($baseDir)
    {
        $this->_baseDir = $baseDir;
    }

    /**
     * @param string $path
     * @return array
     */
    public function getEntities($path)
    {
        $entities = array();

        $Directory = new \RecursiveDirectoryIterator($path);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $objects = new \RegexIterator($Iterator, '/.*(.php)$/', \RecursiveRegexIterator::GET_MATCH);

        foreach($objects as $filename => $object){
            $file = explode('/', $filename);
            if(!preg_filter($this->_ignoredFolder, array(), $file))
            {
                $namespace = $this->getNamespace($filename);
                if ($this->_isValidNamespace($namespace)) {
                    $entities[] = $namespace;
                }
            }
        }

        return $entities;
    }

    public function parseFiles()
    {

    }
    
    public function addIgnoredFolder($folder)
    {
        $this->_ignoredFolder[] = $folder;
    }

    /**
     * @param string $file
     * @return string
     */
    public function getNamespace($file)
    {
        $classname = str_replace(array($this->_baseDir, 'bundle', '.php', '/'), array('', 'Bundle', '', '\\'), $file);
        return (strpos($classname, 'BackBuilder') === false) ? 'BackBuilder' . $classname : $classname;
    }
    
    private function _isValidNamespace($namespace)
    {
        return (
            true === class_exists($namespace) &&
            $this->_isEntity(new \ReflectionClass($namespace))
        );
    }

    /**
     * @param \ReflectionClass $reflection
     * @return boolean
     */
    private function _isEntity(\ReflectionClass $reflection)
    {
        return (!is_null($this->_getEntityAnnotation($reflection)));
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
