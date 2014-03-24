<?php

namespace BackBuilder\Installer;

use BackBuilder\BBApplication;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Installer
 * @copyright   Lp system
 * @author      nicolas dufreche <n.dufreche@lp-digital.fr>
 */
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
     * @var SchemaTool 
     */
    private $_schemaTool;

    /**
     * @var EntityFinder
     */
    private $_entityFinder;

    /**
     * @param \BackBuilder\BBApplication $application
     */
    public function __construct(BBApplication $application)
    {
        $this->_application = $application;
        $this->_em = $this->_application->getEntityManager();
        $this->_schemaTool = new SchemaTool($this->_em);
        $this->_entityFinder = new EntityFinder(dirname($this->_application->getBBDir()));
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
        foreach ($this->_application->getBundles() as $bundle) {
            $this->createBundleSchema($bundle->getId());
        }
    }

    /**
     * Create the bundle schema specified in param
     * 
     * @param string $bundleName
     */
    public function createBundleSchema($bundleName)
    {
        if (null === $bundle = $this->_application->getBundle($bundleName)) {
            return;
        }

        $schemaTool = new SchemaTool($bundle->getEntityManager());
        $classes = $this->_getBundleSchema($bundle);
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
        unset($schemaTool);
    }

    /**
     * update backbuilder schema.
     */
    public function updateBackbuilderSchema()
    {
        $this->_schemaTool->updateSchema($this->_getBackbuilderSchema(), true);
    }

    /**
     * update all bundles schema.
     */
    public function updateBundlesSchema()
    {
        foreach ($this->_application->getBundles() as $bundle) {
            $this->updateBundleSchema($bundle->getId());
        }
    }

    /**
     * update the bundle schema specified in param
     * 
     * @param string $bundleName
     */
    public function updateBundleSchema($bundleName)
    {
        if (null === $bundle = $this->_application->getBundle($bundleName)) {
            return;
        }

        $schemaTool = new SchemaTool($bundle->getEntityManager());
        $classes = $this->_getBundleSchema($bundle);
        $schemaTool->updateSchema($classes, true);
        unset($schemaTool);
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
        foreach ($this->_entityFinder->getEntities($this->_application->getBBDir()) as $className) {
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
        foreach ($this->_entityFinder->getEntities(dirname($reflection->getFileName())) as $className) {
            $classes[] = $this->_em->getClassMetadata($className);
        }
        return $classes;
    }

}