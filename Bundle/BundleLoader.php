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
use BackBuilder\DependencyInjection\Util\ServiceLoader;
use BackBuilder\Exception\InvalidArgumentException;
use BackBuilder\IApplication;
use BackBuilder\Util\Resolver\BundleConfigDirectory;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * BundleLoader allows us to hydrate bundle into IApplication and its service container
 *
 * @category    BackBuilder
 * @package     BackBuilder/Bundle
 * @copyright   Lp digital system
 * @author      eric.chau <eric.chau@lp-digital.fr>
 */
class BundleLoader
{
    const EVENT_RECIPE_KEY = 'event';
    const SERVICE_RECIPE_KEY = 'service';
    const CLASSCONTENT_RECIPE_KEY = 'classcontent';
    const TEMPLATE_RECIPE_KEY = 'template';
    const HELPER_RECIPE_KEY = 'helper';
    const RESOURCE_RECIPE_KEY = 'resource';
    const NAMESPACE_RECIPE_KEY = 'namespace';
    const CUSTOM_RECIPE_KEY = 'custom';
    const ROUTE_RECIPE_KEY = 'route';

    /**
     * [$application description]
     * @var [type]
     */
    private $application;

    /**
     * [$container description]
     * @var [type]
     */
    private $container;

    /**
     * [$bundles_base_directory description]
     * @var [type]
     */
    private $bundles_base_directory;

    /**
     * [$reflection_classes description]
     * @var [type]
     */
    private $reflection_classes;

    /**
     * [__construct description]
     * @param IApplication $application [description]
     */
    public function __construct(IApplication $application)
    {
        $this->application = $application;
        $this->container = $application->getContainer();
        $this->bundles_base_directory = array();
        $this->reflection_classes = array();
    }

    /**
     * [load description]
     * @param  array  $bundles_config [description]
     * @return [type]                 [description]
     */
    public function load(array $bundles_config)
    {
        foreach ($bundles_config as $id => $classname) {
            $service_id = $this->generateBundleServiceId($id);

            if (false === $this->container->hasDefinition($service_id)) {
                $base_directory = $this->buildBundleBaseDirectoryFromClassname($classname);
                $this->bundles_base_directory[$service_id] = $base_directory;
                $this->container->setDefinition(
                    $service_id,
                    $this->buildBundleDefinition($classname, $base_directory)
                );
            }
        }

        if (0 < count($this->bundles_base_directory)) {
            $this->loadFullBundles();
        }
    }

    /**
     * [buildBundleBaseDirectoryFromClassname description]
     *
     * @param  [type] $classname [description]
     *
     * @return [type]            [description]
     */
    public function buildBundleBaseDirectoryFromClassname($classname)
    {
        if (false === array_key_exists($classname, $this->reflection_classes)) {
            $this->reflection_classes[$classname] = new \ReflectionClass($classname);
        }

        $base_directory = dirname($this->reflection_classes[$classname]->getFileName());

        if (false === is_dir($base_directory)) {
            throw new \Exception();
        }

        return $base_directory;
    }

    /**
     * [loadConfigDefinition description]
     *
     * @param  [type] $config_id      [description]
     * @param  [type] $base_directory [description]
     */
    public function loadConfigDefinition($config_id, $base_directory)
    {
        if (false === $this->container->hasDefinition($config_id)) {
            $this->container->setDefinition($config_id, $this->buildConfigDefinition($base_directory));
        }
    }

    /**
     * [loadBundlesRoutes description]
     */
    public function loadBundlesRoutes()
    {
        $loaded_bundles = array_keys($this->container->findTaggedServiceIds('bundle.config'));
        foreach ($loaded_bundles as $service_id) {
            $config = $this->container->get($service_id);
            $recipes = $this->getLoaderRecipesByConfig($config);

            $this->loadRoutes($config, $this->getCallableByRecipesAndKey($recipes, self::ROUTE_RECIPE_KEY));
        }
    }

    /**
     * [generateBundleKey description]
     * @param  [type] $id [description]
     * @return [type]       [description]
     */
    private function generateBundleServiceId($id)
    {
        return str_replace('%bundle_name%', strtolower($id), BundleInterface::BUNDLE_SERVICE_ID_PATTERN);
    }

    /**
     * [buildBundleDefinition description]
     * @param  [type] $classname [description]
     * @return [type]            [description]
     */
    private function buildBundleDefinition($classname, $base_directory)
    {
        if (false === in_array('BackBuilder\Bundle\BundleInterface', class_implements($classname))) {
            throw new InvalidArgumentException(
                "Bundles must implements `BackBuilder\Bundle\BundleInterface`, `$classname` does not."
            );
        }

        $definition = new Definition($classname, array(new Reference('bbapp')));
        $definition->addTag('bundle', array('dispatch_event' => false));
        $definition->addMethodCall('start');

        return $definition;
    }

    /**
     * [areBundlesAlreadyRestored description]
     */
    private function loadFullBundles()
    {
        foreach ($this->bundles_base_directory as $base_directory) {
            $config = $this->loadAndGetBundleConfigByBaseDirectory($base_directory);
            $recipes = $this->getLoaderRecipesByConfig($config);

            $this->loadServices($config, $this->getCallableByRecipesAndKey($recipes, self::SERVICE_RECIPE_KEY));

            $this->loadEvents($config, $this->getCallableByRecipesAndKey($recipes, self::EVENT_RECIPE_KEY));

            $this->loadRoutes($config, $this->getCallableByRecipesAndKey($recipes, self::ROUTE_RECIPE_KEY));

            $this->registerClassContentDirectory(
                $config,
                $this->getCallableByRecipesAndKey($recipes, self::CLASSCONTENT_RECIPE_KEY)
            );

            $this->registerTemplatesDirectory(
                $config,
                $this->getCallableByRecipesAndKey($recipes, self::TEMPLATE_RECIPE_KEY)
            );

            $this->registerHelpersDirectory(
                $config,
                $this->getCallableByRecipesAndKey($recipes, self::HELPER_RECIPE_KEY)
            );

            $this->registerResourcesDirectory(
                $config,
                $this->getCallableByRecipesAndKey($recipes, self::RESOURCE_RECIPE_KEY)
            );

            $this->registerNamespaces(
                $config,
                $this->getCallableByRecipesAndKey($recipes, self::NAMESPACE_RECIPE_KEY)
            );

            $this->runRecipe(
                $config,
                $this->getCallableByRecipesAndKey($recipes, self::CUSTOM_RECIPE_KEY)
            );
        }
    }

    /**
     * [loadBundlesConfig description]
     * @return [type] [description]
     */
    private function loadAndGetBundleConfigByBaseDirectory($base_directory)
    {
        $config_id = str_replace('%bundle_id%', basename($base_directory), BundleInterface::CONFIG_SERVICE_ID_PATTERN);
        $this->loadConfigDefinition($config_id, $base_directory);
        $bundle_config = $this->container->get($config_id)->getBundleConfig();
        if (true === isset($bundle_config['config_per_site']) && true === $bundle_config['config_per_site']) {
            $definition = $this->container->getDefinition($config_id);
            $definition->addTag('config_per_site');
        }

        return $this->container->get($config_id);
    }

    /**
     * [buildConfigDefinition description]
     *
     * @param  [type] $base_directory [description]
     *
     * @return [type]                 [description]
     */
    private function buildConfigDefinition($base_directory)
    {
        $definition = new Definition('BackBuilder\Config\Config', array(
            $this->getConfigDirectoryByBaseDirectory($base_directory),
            new Reference('cache.bootstrap'),
            null,
            '%debug%',
            '%config.yml_files_to_ignore%'
        ));

        if (true === $this->application->getContainer()->getParameter('container.autogenerate')) {
            $definition->addTag('dumpable', array('dispatch_event' => false));
        }

        $definition->addMethodCall('setContainer', array(new Reference('service_container')));
        $definition->addMethodCall('setEnvironment', array('%bbapp.environment%'));
        $definition->setConfigurator(array(new Reference('config.configurator'), 'configureBundleConfig'));
        $definition->addTag('bundle.config', array('dispatch_event' => false));

        return $definition;
    }

    /**
     * [getConfigDirectoryByBaseDirectory description]
     *
     * @param  [type] $base_directory [description]
     *
     * @return [type]                 [description]
     */
    private function getConfigDirectoryByBaseDirectory($base_directory)
    {
        $directory = $base_directory . DIRECTORY_SEPARATOR . BundleInterface::CONFIG_DIRECTORY_NAME;
        if (false === is_dir($directory)) {
            $directory = $base_directory . DIRECTORY_SEPARATOR . BundleInterface::OLD_CONFIG_DIRECTORY_NAME;
        }

        return $directory;
    }

    /**
     * [getLoaderRecipesByConfig description]
     *
     * @param  Config $config [description]
     * @return [type]         [description]
     */
    private function getLoaderRecipesByConfig(Config $config)
    {
        $recipes = null;
        $bundle_config = $config->getBundleConfig();
        if (null !== $bundle_config && true === array_key_exists('bundle_loader_recipes', $bundle_config)) {
            $recipes = $bundle_config['bundle_loader_recipes'];
        }

        return $recipes;
    }

    /**
     * [getCallableByRecipesAndKey description]
     * @param  [type] $recipes [description]
     * @param  [type] $key     [description]
     * @return [type]          [description]
     */
    private function getCallableByRecipesAndKey(array $recipes = null, $key)
    {
        $recipe = null;
        if (null !== $recipes && true === array_key_exists($key, $recipes) && true === is_callable($recipes[$key])) {
            $recipe = $recipes[$key];
        }

        return $recipe;
    }

    /**
     * [loadServices description]
     *
     * @param  Config $config [description]
     * @param  [type] $recipe [description]
     */
    private function loadServices(Config $config, callable $recipe = null)
    {
        if (false === $this->runRecipe($config, $recipe)) {
            $directories = BundleConfigDirectory::getDirectories(
                $this->application->getBaseRepository(),
                $this->application->getContext(),
                $this->application->getEnvironment(),
                basename(dirname($config->getBaseDir()))
            );

            array_unshift($directories, $this->getConfigDirectoryByBaseDirectory(dirname($config->getBaseDir())));

            foreach ($directories as $directory) {
                $filepath = $directory . DIRECTORY_SEPARATOR . 'services.xml';
                if (true === is_file($filepath) && true === is_readable($filepath)) {
                    try {
                        ServiceLoader::loadServicesFromXmlFile($this->container, $directory);
                    } catch (\Exception $e) { /* nothing to do, just ignore it */ }
                }
            }
        }
    }

    /**
     * [loadEvents description]
     *
     * @param  Config $config [description]
     * @param  [type] $recipe [description]
     */
    private function loadEvents(Config $config, callable $recipe = null)
    {
       if (false === $this->runRecipe($config, $recipe)) {
            $events = $config->getRawSection('events');
            if (true === is_array($events) || 0 < count($events)) {
                $this->application->getEventDispatcher()->addListeners($events);
            }
        }
    }

    /**
     * [registerClassContentDirectory description]
     *
     * @param  Config $config [description]
     * @param  [type] $recipe [description]
     */
    private function registerClassContentDirectory(Config $config, callable $recipe = null)
    {
        if (null !== $recipe) {
            $this->runRecipe($config, $recipe);
        } else {
            $directory = realpath(dirname($config->getBaseDir()) . DIRECTORY_SEPARATOR . 'ClassContent');
            if (false !== $directory) {
                $this->application->pushClassContentDir($directory);
            }
        }
    }

    /**
     * [registerTemplatesDirectory description]
     *
     * @param  Config $config [description]
     * @param  [type] $recipe [description]
     */
    private function registerTemplatesDirectory(Config $config, callable $recipe = null)
    {
        if (false === $this->runRecipe($config, $recipe)) {
            $directory = realpath(
                dirname($config->getBaseDir()) . DIRECTORY_SEPARATOR
                . 'Templates' . DIRECTORY_SEPARATOR . 'scripts'
            );
            if (false !== $directory) {
                $this->application->getRenderer()->addScriptDir($directory);
            }
        }
    }

    /**
     * [registerHelpersDirectory description]
     *
     * @param  Config $config [description]
     * @param  [type] $recipe [description]
     */
    private function registerHelpersDirectory(Config $config, callable $recipe = null)
    {
        if (false === $this->runRecipe($config, $recipe)) {
            $directory = realpath(
                dirname($config->getBaseDir()) . DIRECTORY_SEPARATOR
                . 'Templates' . DIRECTORY_SEPARATOR . 'helpers'
            );

            if (false !== $directory) {
                $this->application->getRenderer()->addHelperDir($directory);
            }
        }
    }

    /**
     * [loadRoutes description]
     *
     * @param  Config $config [description]
     * @param  [type] $recipe [description]
     */
    private function loadRoutes(Config $config, callable $recipe = null)
    {
        if (false === $this->runRecipe($config, $recipe)) {
            $route = $config->getRouteConfig();
            if (true === is_array($route) && 0 < count($route)) {
                $this->application->getController()->registerRoutes(
                    $this->generateBundleServiceId(basename(dirname($config->getBaseDir()))),
                    $route
                );
            }
        }
    }

    /**
     * [registerResourcesDirectory description]
     *
     * @param  Config $config [description]
     * @param  [type] $recipe [description]
     */
    private function registerResourcesDirectory(Config $config, callable $recipe = null)
    {
        if (false === $this->runRecipe($config, $recipe)) {
            $base_directory = dirname($config->getBaseDir()) . DIRECTORY_SEPARATOR;
            $directory = realpath($base_directory . DIRECTORY_SEPARATOR . 'Resources');
            if (false === $directory) {
                $directory = realpath($base_directory . DIRECTORY_SEPARATOR . 'Ressources');
            }

            if (false !== $directory) {
                $this->application->pushResourceDir($directory);
            }
        }
    }

    /**
     * [registerNamespaces description]
     *
     * @param  Config $config [description]
     * @param  [type] $recipe [description]
     */
    private function registerNamespaces(Config $config, callable $recipe = null)
    {
        $this->runRecipe($config, $recipe);
    }

    /**
     * [runRecipe description]
     *
     * @param  Config $config [description]
     * @param  [type] $recipe [description]
     *
     * @return [type]         [description]
     */
    private function runRecipe(Config $config, callable $recipe = null)
    {
        $done = false;
        if (null !== $recipe) {
            call_user_func_array($recipe, array($this->application, $config));
            $done = true;
        }

        return $done;
    }
}
