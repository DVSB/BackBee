<?php
namespace BackBuilder\Bundle;

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

use BackBuilder\IApplication;
use BackBuilder\Bundle\BundleInterface;
use BackBuilder\Config\Config;
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
    const RESOURCE_RECIPE_KEY = 'resource';
    const TEMPLATE_RECIPE_KEY = 'template';
    const HELPER_RECIPE_KEY = 'helper';
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
     * [__construct description]
     * @param IApplication $application [description]
     */
    public function __construct(IApplication $application)
    {
        $this->application = $application;
        $this->container = $application->getContainer();
        $this->bundles_base_directory = array();
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
                $this->container->setDefinition($service_id, $this->buildBundleDefinition($classname, $base_directory));

            }
        }

        if (0 < count($this->bundles_base_directory)) {
            $this->loadFullBundles();
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
     * [buildBundleBaseDirectoryFromClassname description]
     * @param  [type] $classname [description]
     * @return [type]            [description]
     */
    private function buildBundleBaseDirectoryFromClassname($classname)
    {
        preg_match('#([a-zA-Z]+Bundle)#', $classname, $matches);

        if (0 === count($matches)) {
            throw new \Exception();
        }

        $base_directory = implode(DIRECTORY_SEPARATOR, array(
            $this->application->getBaseDir(),
            BundleInterface::BACKBEE_BUNDLE_DIRECTORY_NAME,
            $matches[0]
        ));
        if (false === is_dir($base_directory)) {
            throw new \Exception();
        }

        return $base_directory;
    }

    /**
     * [buildBundleDefinition description]
     * @param  [type] $classname [description]
     * @return [type]            [description]
     */
    private function buildBundleDefinition($classname, $base_directory)
    {
        $definition = new Definition($classname, array(new Reference('bbapp')));
        $definition->addMethodCall('setBaseDirectory', array($base_directory));
        $definition->addTag('bundle');

        return $definition;
    }

    /**
     * [areBundlesAlreadyRestored description]
     * @return [type] [description]
     */
    private function loadFullBundles()
    {
$start = microtime(true);
        foreach ($this->bundles_base_directory as $base_directory) {
            $config = $this->loadAndGetBundleConfigByBaseDirectory($base_directory);
            $recipes = $this->getLoaderRecipesByConfig($config);

            $this->loadBundlesServices($config, $this->getCallableByRecipesAndKey($recipes, self::SERVICE_RECIPE_KEY));
            $this->loadBundlesEvents($config, $this->getCallableByRecipesAndKey($recipes, self::EVENT_RECIPE_KEY));
            // $this->registerBundleClassContentDirectories();
            // $this->registerBundleHelperDirectories();
            // $this->registerBundleTemplateDiretory();
        }
echo number_format((microtime(true) - $start), 6) . 's<br>';
 die;
    }

    /**
     * [loadBundlesConfig description]
     * @return [type] [description]
     */
    private function loadAndGetBundleConfigByBaseDirectory($base_directory)
    {
        $config_id = str_replace('%bundle_id%', basename($base_directory), BundleInterface::CONFIG_SERVICE_ID_PATTERN);
        $this->container->setDefinition($config_id, $this->buildConfigDefinition($base_directory));

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
        // $definition->addTag('dumpable');
        $definition->addMethodCall('setContainer', array(new Reference('service_container')));
        $definition->addMethodCall('setEnvironment', array('%bbapp.environment%'));
        $definition->setConfigurator(array(new Reference('bundle_config_configurator'), 'configure'));

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
     * [loadBundlesServices description]
     *
     * @param  Config $config  [description]
     * @param  [type] $recipes [description]
     */
    private function loadBundlesServices(Config $config, callable $recipe = null)
    {
        if (null !== $recipe) {
            call_user_func_array($recipe, array($this->application, $config));
        } else {
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
     * [loadBundlesEvents description]
     *
     * @param  Config $config [description]
     * @param  [type] $recipe [description]
     * @return [type]         [description]
     */
    private function loadBundlesEvents(Config $config, callable $recipe = null)
    {
       if (null !== $recipe) {
            call_user_func_array($recipe, array($this->application, $config));
        } else {
            $events = $config->getRawSection('events');
            if (true === is_array($events) || 0 < count($events)) {
                $this->application->getEventDispatcher()->addListeners($events);
            }
        }
    }

    /**
     * [loadBundlesIntoApplication description]
     * @param  BBApplication $application  [description]
     * @param  array         $bundles_config [description]
     * @return [type]                      [description]
     */
    public static function loadBundlesIntoApplication(BBApplication $application, array $bundles_config)
    {
        self::registerBundleClassContentDir();
        self::registerBundleResourceDir();
        self::registerBundleScriptDir();
        self::registerBundleHelperDir();
    }


    private static function registerBundleClassContentDir()
    {
        if (true === self::$application->isRestored()) {
            return;
        }

        foreach (self::$bundles_base_dir as $key => $dir) {
            $config = self::$application->getContainer()->get($key . '.config');
            $recipe = null;
            if (null !== $config) {
                $recipe = self::getBundleLoaderRecipeFor($config, self::CLASSCONTENT_RECIPE_KEY);
            }

            if (null === $recipe) {
                $classcontent_dir = realpath($dir . DIRECTORY_SEPARATOR . 'ClassContent');
                if (false === $classcontent_dir) {
                    continue;
                }

                self::$application->pushClassContentDir($classcontent_dir);
            } else {
                if (null !== $config && true === is_callable($recipe)) {
                    call_user_func_array($recipe, array(self::$application, $config));
                }
            }
        }
    }

    private static function registerBundleResourceDir()
    {
        if (true === self::$application->isRestored()) {
            return;
        }

        foreach (self::$bundles_base_dir as $key => $dir) {
            $config = self::$application->getContainer()->get($key . '.config');
            $recipe = null;
            if (null !== $config) {
                $recipe = self::getBundleLoaderRecipeFor($config, self::RESOURCE_RECIPE_KEY);
            }

            if (null === $recipe) {
                $resources_dir = realpath($dir . DIRECTORY_SEPARATOR . 'Ressources');
                if (false === $resources_dir) {
                    continue;
                }

                self::$application->pushResourceDir($resources_dir);
            } else {
                if (null !== $config && true === is_callable($recipe)) {
                    call_user_func_array($recipe, array(self::$application, $config));
                }
            }
        }
    }

    private static function registerBundleScriptDir()
    {
        $renderer = self::$application->getRenderer();
        if (true === $renderer->isRestored()) {
            return;
        }

        foreach (self::$bundles_base_dir as $key => $dir) {
            $config = self::$application->getContainer()->get($key . '.config');
            $recipe = null;
            if (null !== $config) {
                $recipe = self::getBundleLoaderRecipeFor($config, self::TEMPLATE_RECIPE_KEY);
            }

            if (null === $recipe) {
                $scripts_dir = realpath($dir . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'scripts');
                if (false === $scripts_dir) {
                    continue;
                }

                $renderer->addScriptDir($scripts_dir);
            } else {
                if (null !== $config && true === is_callable($recipe)) {
                    call_user_func_array($recipe, array(self::$application, $config));
                }
            }
        }
    }

    private static function registerBundleHelperDir()
    {
        if (true === self::$application->getAutoloader()->isRestored()) {
            return;
        }

        $renderer = self::$application->getRenderer();
        foreach (self::$bundles_base_dir as $key => $dir) {
            $config = self::$application->getContainer()->get($key . '.config');
            $recipe = null;
            if (null !== $config) {
                $recipe = self::getBundleLoaderRecipeFor($config, self::HELPER_RECIPE_KEY);
            }

            if (null === $recipe) {
                $helper_dir = realpath($dir . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'helpers');
                if (false === $helper_dir) {
                    continue;
                }

                $renderer->addHelperDir($helper_dir);
            } else {
                if (null !== $config && true === is_callable($recipe)) {
                    call_user_func_array($recipe, array(self::$application, $config));
                }
            }
        }
    }
}
