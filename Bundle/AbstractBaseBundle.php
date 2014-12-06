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
     * Application this bundle belongs to
     *
     * @var BackBuilder\IApplication
     */
    private $application;

    /**
     * Bundle loader of current application
     *
     * @var BackBuilder\Bundle\BundleLoader
     */
    private $bundle_loader;

    /**
     * Bundle base directory
     *
     * @var string
     */
    private $base_directory;

    /**
     * Bundle identifier
     *
     * @var string
     */
    private $id;

    /**
     * Bundle's config identifier
     *
     * @var string
     */
    private $config_id;

    /**
     * Define if this bundle is already started or not
     *
     * @var boolean
     */
    private $started;

    /**
     * Formatted list of this bundle exposed actions
     *
     * @var array
     */
    private $exposed_actions;

    /**
     * Indexed by a unique name (controller name + action name), it contains every (controller; action)
     * callbacks
     *
     * @var array
     */
    private $exposed_actions_callbacks;

    /**
     * AbstractBaseBundle's constructor
     *
     * @param ApplicationInterface $application application to link current bundle with
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->application = $application;
        $this->bundle_loader = $this->application->getContainer()->get('bundle.loader');

        $this->base_directory = $this->bundle_loader->buildBundleBaseDirectoryFromClassname(get_class($this));
        $this->id = basename($this->base_directory);

        $this->bundle_loader->loadConfigDefinition($this->getConfigServiceId(), $this->base_directory);

        $this->exposed_actions = array();
        $this->exposed_actions_callbacks = array();
        $this->initBundleExposedActions();

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
     * Bundle's config service id getter
     *
     * @return string
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
     * Bundle base directory getter
     *
     * @return string
     */
    public function getConfigDirectory()
    {
        $directory = $this->base_directory.DIRECTORY_SEPARATOR.BundleInterface::CONFIG_DIRECTORY_NAME;
        if (false === is_dir($directory)) {
            $directory = $this->base_directory.DIRECTORY_SEPARATOR.BundleInterface::OLD_CONFIG_DIRECTORY_NAME;
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
     * enable property setter
     *
     * @param boolean $enable
     *
     * @return self
     */
    public function setEnable($enable)
    {
        $properties = $this->getProperty();
        $properties['enable'] = (boolean) $enable;
        $this->getConfig()->setSection('bundle', $properties, true);

        return $this;
    }

    /**
     * category property setter
     *
     * @param string|array $category
     *
     * @return self
     */
    public function setCategory($category)
    {
        $properties = $this->getProperty();
        $properties['category'] = (array) $category;
        $this->getConfig()->setSection('bundle', $properties, true);

        return $this;
    }

    /**
     * config_per_site property setter
     *
     * @param boolean $v
     *
     * @return self
     */
    public function setConfigPerSite($v)
    {
        $properties = $this->getProperty();
        $properties['config_per_site'] = (boolean) $v;
        $this->getConfig()->setSection('bundle', $properties, true);

        return $this;
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

        $obj->exposed_actions = $this->getExposedActionsMapping();

        return $obj;
    }

    /**
     * @codeCoverageIgnore
     * @see Symfony\Component\Security\Acl\Model\DomainObjectInterface::getObjectIdentifier
     */
    public function getObjectIdentifier()
    {
        return $this->getType().'('.$this->getIdentifier().')';
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

    /**
     * @deprecated 0.10 it's still here to maintain compatibility between old and new core js
     */
    public function serialize()
    {
        $obj = new \stdClass();
        $obj->id = $this->getId();

        foreach ($this->getProperty() as $key => $value) {
            $obj->$key = $value;
        }

        return json_encode($obj);
    }

    /**
     * Bundle's exposed actions getter
     *
     * @return array
     */
    public function getExposedActionsMapping()
    {
        return $this->exposed_actions;
    }

    /**
     * Returns the associated callback if "controller name/action name" couple is valid
     *
     * @param string $controller_name the controller name (ex.: BackBuilder\FrontController\FrontController => front)
     * @param string $action_name     the action name (ex.:)
     *
     * @return callable|null the callback if there is one associated to "controller name/action name" couple, else null
     */
    public function getExposedActionCallback($controller_name, $action_name)
    {
        $unique_name = $controller_name.'_'.$action_name;

        return array_key_exists($unique_name, $this->exposed_actions_callbacks)
            ? $this->exposed_actions_callbacks[$unique_name]
            : null
        ;
    }

    /**
     * Initialize bundle exposed actions by building exposed_actions array and exposed_actions_callback array
     */
    private function initBundleExposedActions()
    {
        if (true === $this->isEnabled()) {
            $container = $this->getApplication()->getContainer();
            foreach ((array) $this->getProperty('exposed_actions') as $controller_id => $actions) {
                if (false === $container->has($controller_id)) {
                    throw new \InvalidArgumentException(
                        "Exposed controller with id `$controller_id` not found for ".$this->getId()
                    );
                }

                $controller = $container->get($controller_id);
                $this->formatAndInjectExposedAction($controller, $actions);
            }
        }
    }

    /**
     * Format a valid map between controller and actions and hydrate
     *
     * @param BundleExposedControllerInterface $controller
     * @param array                            $actions
     */
    private function formatAndInjectExposedAction($controller, $actions)
    {
        $controller_id = explode('\\', get_class($controller));
        $controller_id = str_replace('controller', '', strtolower(array_pop(($controller_id))));
        $this->exposed_actions[$controller_id] = array('actions' => array());

        if ($controller instanceof BundleExposedControllerInterface) {
            $this->exposed_actions[$controller_id]['label'] = $controller->getLabel();
            $this->exposed_actions[$controller_id]['description'] = $controller->getDescription();
            array_unshift($actions, 'indexAction');
            $actions = array_unique($actions);
        }

        foreach ($actions as $action) {
            if (method_exists($controller, $action)) {
                $action_id = str_replace('action', '', strtolower($action));
                $unique_name = $controller_id.'_'.$action_id;

                $this->exposed_actions_callbacks[$unique_name] = array($controller, $action);
                $this->exposed_actions[$controller_id]['actions'][] = $action_id;
            }
        }
    }
}
