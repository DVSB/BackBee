<?php

namespace BackBuilder\Installer;

use BackBuilder\BBApplication;
use BackBuilder\Installer\Annotation\Fixture as AnnotationFixture;
use BackBuilder\Installer\Annotation\Fixtures as AnnotationFixtures;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;

class Fixture
{
    private $_application;
    private $_cacheDir;
    private $_annotationReader;

    public function __construct(BBApplication $application)
    {
        $this->_application = $application;
        $this->_cacheDir = $this->_application->getCacheDir().DIRECTORY_SEPARATOR.'Fixtures';
        if (!file_exists($this->_cacheDir)) {
            mkdir($this->_cacheDir);
        }
        $this->_entityFinder = new EntityFinder($this->_application->getBaseDir());
    }

    public function populate()
    {
        $classes = array();
        foreach ($this->_entityFinder->getEntities($this->_application->getBBDir()) as $className) {
            $classes[] = $this->_application->getEntityManager()->getClassMetadata($className);
        }
        foreach ($classes as $classMetadata) {
            if ($this->isFixturedEntity($classMetadata->getReflectionClass())) {
                $class = $this->getClass($classMetadata);
            }
        }
    }

    public function populateClass(ClassMetadata $classMetadata)
    {
        if (!$this->isFixturedEntity($classMetadata->getReflectionClass())) {
            return;
        }
        $class = $this->getClass($classMetadata);
        for ($i = 0; $i < $this->getClassAnnotation($classMetadata->getReflectionClass())->qty; $i++) {
            $entity = $class->setUp();
            $this->_application->getEntityManager()->persist($entity);
            $this->_application->getEntityManager()->flush();
        }
    }

    public function getClass(ClassMetadata $classMetadata)
    {
        if (
            !file_exists($this->_cacheDir.str_replace('\\', '_', $classMetadata->getName()))
        ) {
            $this->createProxyFile($classMetadata);
        }
        include_once $this->_cacheDir.DIRECTORY_SEPARATOR.str_replace('\\', '_', $classMetadata->getName());
        $className = $classMetadata->getName().'\\Fixture';

        return new $className();
    }

    private function createProxyFile(ClassMetadata $classMetadata)
    {
        $fileName = str_replace('\\', '_', $classMetadata->getName());
        $reflection = $classMetadata->getReflectionClass();
        $filecontent = $this->getFileContent($classMetadata->getName());

        foreach ($reflection->getProperties() as $property) {
            /* @var $property \ReflectionProperty */
            $annotation = $this->getPropertyAnnotation($property);
            if (!is_null($annotation)) {
                $filecontent .= '        $obj->'.$property->getName().' = '.$annotation->getFixture()."\n";
            }
        }
        foreach ($classMetadata->getAssociationMappings() as $association) {
            $filecontent .= '        $obj->'.$association['fieldName'].' = array_key_exists("'.$association['fieldName'].'", $dpdc) ? $dpdc["'.$association['fieldName'].'"] : null;'."\n";
        }
        $filecontent .= '        return $obj;'."\n".'    }'."\n".'}';
        file_put_contents($this->_cacheDir.DIRECTORY_SEPARATOR.$fileName, $filecontent);
    }

    private function getFileContent($className)
    {
        $filecontent = '<?php'."\n";
        $filecontent .= 'namespace '.$className.";\n\n";
        $filecontent .= 'class Fixture extends \\'.$className."\n";
        $filecontent .= '{'."\n";
        $filecontent .= '    private $faker;'."\n\n";
        $filecontent .= '    public function __construct(){'."\n";
        $filecontent .= '        $this->faker = \Faker\Factory::create();'."\n";
        $filecontent .= '    }'."\n\n";
        $filecontent .= '    public function setUp(array $dpdc = array()) {'."\n";
        $filecontent .= '        $obj = new \\'.$className.'();'."\n";

        return $filecontent;
    }

    private function isFixturedEntity(\ReflectionClass $class)
    {
        return !is_null($this->getClassAnnotation($class));
    }

    /**
     * @param  \ReflectionProperty $property
     * @return AnnotationFixture   or null
     */
    private function getPropertyAnnotation(\ReflectionProperty $property)
    {
        return $this->getAnnotationReader()->getPropertyAnnotation($property, new AnnotationFixture());
    }

    /**
     * @param  \ReflectionProperty $property
     * @return AnnotationFixture   or null
     */
    private function getClassAnnotation(\ReflectionClass $class)
    {
        return $this->getAnnotationReader()->getClassAnnotation($class, new AnnotationFixtures());
    }

    /**
     * @return SimpleAnnotationReader
     */
    private function getAnnotationReader()
    {
        if (!$this->_annotationReader) {
            $this->_annotationReader = new SimpleAnnotationReader();
            $this->_annotationReader->addNamespace('BackBuilder\Installer\Annotation');
        }

        return $this->_annotationReader;
    }
}
