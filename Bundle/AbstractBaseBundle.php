<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\Bundle;

use BackBuilder\Bundle\BundleInterface;
use BackBuilder\Config\Config;
use BackBuilder\IApplication as ApplicationInterface;
use BackBuilder\Security\Acl\Domain\IObjectIdentifiable;
use BackBuilder\Routing\RouteCollection;

use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Abstract base class for BackBee's bundle
 *
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
abstract class AbstractBaseBundle implements BundleInterface
{
    /**
     * [$application description]
     *
     * @var BackBuilder\IApplication
     */
    private $application;

    /**
     * [$bundle_loader description]
     *
     * @var [type]
     */
    private $bundle_loader;

    /**
     * [$base_directory description]
     *
     * @var string
     */
    private $base_directory;

    /**
     * [$base_directory description]
     *
     * @var string
     */
    private $id;

    /**
     * [$config_id description]
     *
     * @var string
     */
    private $config_id;

    /**
     * [$started description]
     *
     * @var boolean
     */
    private $started;

    /**
     * AbstractBaseBundle's constructor
     *
     * @param ApplicationInterface $application [description]
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->application = $application;
        $this->bundle_loader = $this->application->getContainer()->get('bundle.loader');

        $this->base_directory = $this->bundle_loader->buildBundleBaseDirectoryFromClassname(get_class($this));
        $this->id = basename($this->base_directory);

        $this->bundle_loader->loadConfigDefinition($this->getConfigServiceId(), $this->base_directory);

        $this->started = false;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::getId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::getBaseDirectory
     */
    public function getBaseDirectory()
    {
        return $this->base_directory;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::getProperty
     */
    public function getProperty($key = null)
    {
        $properties = null;
        if (null !== $this->getConfig()) {
            $properties = $this->getConfig()->getSection('bundle') ?: array();
        }

        if (null === $key) {
            return $properties;
        }

        $property = null;
        if (true === array_key_exists($key, $properties)) {
            $property = $properties[$key];
        }

        return $property;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::getConfig
     */
    public function getConfig()
    {
        return $this->application->getContainer()->get($this->getConfigServiceId());
    }

    /**
     * [getConfigServiceId description]
     *
     * @return [type] [description]
     */
    public function getConfigServiceId()
    {
        if (null === $this->config_id) {
            $this->config_id = str_replace('%bundle_id%', $this->id, BundleInterface::CONFIG_SERVICE_ID_PATTERN);
            $this->config_id = strtolower($this->config_id);
        }

        return $this->config_id;
    }

    /**
     * [getConfigDirectory description]
     *
     * @return [type] [description]
     */
    public function getConfigDirectory()
    {
        $directory = $this->base_directory . DIRECTORY_SEPARATOR . BundleInterface::CONFIG_DIRECTORY_NAME;
        if (false === is_dir($directory)) {
            $directory = $this->base_directory . DIRECTORY_SEPARATOR . BundleInterface::OLD_CONFIG_DIRECTORY_NAME;
        }

        return $directory;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::isConfigPerSite
     */
    public function isConfigPerSite()
    {
        return null !== $this->getProperty('config_per_site')
            ? $this->getProperty('config_per_site')
            : BundleInterface::DEFAULT_CONFIG_PER_SITE_VALUE
        ;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::getApplication
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::getEntityManager
     */
    public function getEntityManager()
    {
        return $this->application->getEntityManager();
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::isStarted
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::started
     */
    public function started()
    {
        $this->started = true;
    }

    /**
     * @see BackBuilder\Bundle\BundleInterface::isEnabled
     */
    public function isEnabled()
    {
        return true === $this->getProperty('enable');
    }

    /**
     * @see JsonSerializable::jsonSerialize
     */
    public function jsonSerialize()
    {
        $obj = new \stdClass();
        $obj->id = $this->getId();

        foreach ($this->getProperty() as $key => $value) {
            if ('bundle_loader_recipes' !== $key) {
                $obj->$key = $value;
            }
        }

        if (false === property_exists($obj, 'enable')) {
            $obj->enable = true;
        }

        if (false === property_exists($obj, 'config_per_site')) {
            $obj->config_per_site = false;
        }

        if (false === property_exists($obj, 'category')) {
            $obj->category = array();
        }

        if (false === property_exists($obj, 'thumbnail')) {
            $obj->thumbnail = $this->getApplication()->getContainer()->get('routing')
                ->getUri('img/extnd-x/picto-extnd.png', null, null, RouteCollection::RESOURCE_URL)
            ;
        }

        return $obj;
    }

    /**
     * @codeCoverageIgnore
     * @see Symfony\Component\Security\Acl\Model\DomainObjectInterface::getObjectIdentifier
     */
    public function getObjectIdentifier()
    {
        return $this->getType() . '(' . $this->getIdentifier() . ')';
    }

    /**
     * @codeCoverageIgnore
     * @see BackBuilder\Security\Acl\Domain\IObjectIdentifiable::getIdentifier
     */
    public function getIdentifier()
    {
        return $this->getId();
    }

    /**
     * @codeCoverageIgnore
     * @see BackBuilder\Security\Acl\Domain\IObjectIdentifiable::getType
     */
    public function getType()
    {
        return ClassUtils::getRealClass($this);
    }

    /**
     * @codeCoverageIgnore
     * @see BackBuilder\Security\Acl\Domain\IObjectIdentifiable::equals
     */
    public function equals(IObjectIdentifiable $identity)
    {
        return ($this->getType() === $identity->getType() && $this->getIdentifier() === $identity->getIdentifier());
    }
}
