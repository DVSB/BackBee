<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Installer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

use BackBee\BBApplication;

/**
 * @category    BackBee
 * @package     BackBee\Installer
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
     * @param \BackBee\BBApplication      $application
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(BBApplication $application, EntityManager $em = null)
    {
        $this->_application = $application;
        if (null === $em) {
            $this->_em = $this->_application->getEntityManager();
        } else {
            $this->_em = $em;
        }

        $platform = $this->_em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');

        $this->_schemaTool = new SchemaTool($this->_em);
        $this->_entityFinder = new EntityFinder(dirname($this->_application->getBBDir()));
    }

    /**
     * Create the BackBee schema
     */
    public function createBackBeeSchema()
    {
        $classes = $this->_getBackBeeSchema();
        try {
            $this->_schemaTool->dropSchema($classes);
            $this->_schemaTool->createSchema($classes);
        } catch (\Exception $e) {
            echo $e->getMessage()."\n";
        }
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

        try {
            $schemaTool = new SchemaTool($bundle->getEntityManager());
            $classes = $this->_getBundleSchema($bundle);
            $schemaTool->dropSchema($classes);
            $schemaTool->createSchema($classes);
            unset($schemaTool);
        } catch (\Exception $e) {
            echo $e->getMessage()."\n";
        }
    }

    /**
     * update BackBee schema.
     */
    public function updateBackBeeSchema()
    {
        $this->_schemaTool->updateSchema($this->_getBackBeeSchema(), true);
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

        try {
            $schemaTool = new SchemaTool($bundle->getEntityManager());
            $classes = $this->_getBundleSchema($bundle);
            $schemaTool->updateSchema($classes, true);
            unset($schemaTool);
        } catch (\Exception $e) {
            //echo $e->getMessage()."\n";
        }
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
    private function _getBackBeeSchema()
    {
        $classes = array();
        foreach ($this->_entityFinder->getEntities($this->_application->getBBDir()) as $className) {
            $classes[] = $this->_em->getClassMetadata($className);
        }

        return $classes;
    }

    /**
     * @param  \BackBee\Bundle\ABundle $bundle
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

    public function getSqlSchema()
    {
        $sql1 = $this->getBackBeeSqlSchema();
        $sql2 = $this->getBundleSqlSchema();

        $sql = array_merge($sql1, $sql2);

        $sql = implode(";\n", $sql);

        return $sql.';';
    }

    private function getBackBeeSqlSchema()
    {
        $classes = $this->_getBackBeeSchema();
        $sql = $this->_schemaTool->getCreateSchemaSql($classes);

        return $sql;
    }

    private function getBundleSqlSchema()
    {
        $sql = array();

        foreach ($this->_application->getBundles() as $bundle) {
            $classes = $this->_getBundleSchema($bundle);

            $sql = array_merge($sql, $this->_schemaTool->getCreateSchemaSql($classes));
        }

        return $sql;
    }

    /**
     * @param int $type
     *
     * @return array
     */
    public function getUpdateSqlSchema($type = 3)
    {
        $sql1 = $sql2 = array();
        if ($type == 1 || $type & 3 == 3) {
            $sql1 = $this->getUpdateBackBeeSqlSchema();
        }
        if ($type == 2 || $type & 3 == 3) {
            $sql2 = $this->getUpdateBundleSqlSchema();
        }

        $sql = array_merge($sql1, $sql2);

        return $sql;
    }

    private function getUpdateBackBeeSqlSchema()
    {
        $classes = $this->_getBackBeeSchema();
        $sql = $this->_schemaTool->getUpdateSchemaSql($classes, true);

        return $sql;
    }

    private function getUpdateBundleSqlSchema()
    {
        $sql = array();

        foreach ($this->_application->getBundles() as $bundle) {
            $classes = $this->_getBundleSchema($bundle);

            $sql = array_merge($sql, $this->_schemaTool->getUpdateSchemaSql($classes, true));
        }

        return $sql;
    }

    public function getClassMetadata()
    {
        $classes1 = $this->getBackBeeClassMetadata();
        $classes2 = $this->getBundleClassMetadata();

        $classes = array_merge($classes1, $classes2);

        return $classes;
    }

    private function getBackBeeClassMetadata()
    {
        $classes = $this->_getBackBeeSchema();

        return $classes;
    }

    private function getBundleClassMetadata()
    {
        $classes = array();

        foreach ($this->_application->getBundles() as $bundle) {
            $classes = array_merge($classes, $this->_getBundleSchema($bundle));
        }

        return $classes;
    }
}
