<?php
namespace BackBuilder\Bundle;


use BackBuilder\BBApplication;
use BackBuilder\Config\Config;
use BackBuilder\DependencyInjection\ContainerBuilder;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Yaml;

class BundleLoader
{
    const BUNDLE_SERVICE_KEY_PATTERN = 'bundle.%sbundle';

    const EVENT_RECIPE_KEY = 'event';
    const SERVICE_RECIPE_KEY = 'service';
    const CLASSCONTENT_RECIPE_KEY = 'classcontent';
    const RESOURCE_RECIPE_KEY = 'resource';
    const TEMPALTE_RECIPE_KEY = 'template';
    const HELPER_RECIPE_KEY = 'helper';
    const ROUTE_RECIPE_KEY = 'route';

    private static $bundles_base_dir = array();

    private static $bundles_config = array();

    private static $application = null;

    /**
     * [loadBundlesIntoApplication description]
     * @param  BBApplication $application  [description]
     * @param  array         $bundles_config [description]
     * @return [type]                      [description]
     */
    public static function loadBundlesIntoApplication(BBApplication $application, array $bundles_config)
    {
        self::$application = $application;
        $container = $application->getContainer();
        self::$bundles_base_dir = true === $container->hasParameter('bundles.base_dir')
            ? $container->getParameter('bundles.base_dir')
            : array()
        ;

        $do_get_base_dir = !(0 < count(self::$bundles_base_dir)) ?: true;

        foreach ($bundles_config as $name => $classname) {
            $key = sprintf(self::BUNDLE_SERVICE_KEY_PATTERN, strtolower($name));

            if (true === $do_get_base_dir || true === $container->getParameter('debug')) {
                // Using ReflectionClass so we can read every bundle config file without the need to
                // instanciate each bundle's Config
                $r = new \ReflectionClass($classname);
                self::$bundles_base_dir[$key] = dirname($r->getFileName());
            }

            $definition = new Definition($classname, array(new Reference('bbapp')));
            $definition->addTag('bundle');
            $container->setDefinition($key, $definition);
        }

        $container->setParameter('bundles.base_dir', self::$bundles_base_dir);

        self::loadBundlesConfig(); // 222 ms
        self::loadBundleEvents();
        self::loadBundlesServices(); // 17 ms
        self::registerBundleClassContentDir();
        self::registerBundleResourceDir();
        self::registerBundleScriptDir();
        self::registerBundleHelperDir();

        // add BundleListener event (service.tagged.bundle)
        $application->getContainer()->get('event.dispatcher')->addListeners(array(
            'bbapplication.start' => array(
                'listeners' => array(
                    array(
                        'BackBuilder\Bundle\Listener\BundleListener',
                        'onApplicationStart'
                    )
                )
            ),
            'service.tagged.bundle' => array(
                'listeners' => array(
                    array(
                        'BackBuilder\Bundle\Listener\BundleListener',
                        'onGetBundleService'
                    )
                )
            ),
            'bbapplication.stop' => array(
                'listeners' => array(
                    array(
                        'BackBuilder\Bundle\Listener\BundleListener',
                        'onApplicationStop'
                    )
                )
            )
        ));

        // Cleaning memory
        self::$bundles_base_dir = null;
        self::$bundles_config = null;
        self::$application = null;
    }

    /**
     * [loadBundlesConfig description]
     */
    private static function loadBundlesConfig()
    {
        $services_id = array();
        foreach (self::$bundles_base_dir as $key => $base_dir) {
            $config = \BackBuilder\Bundle\ABundle::initBundleConfig(self::$application, $base_dir);
            self::$bundles_config[$key] = $config;
            $config_service_id = \BackBuilder\Bundle\ABundle::getBundleConfigServiceId($base_dir);
            self::$application->getContainer()->set($config_service_id, $config);
            $services_id[] = $config_service_id;
        }

        self::$application->getContainer()->get('registry')->set('bundle.config_services_id', $services_id);
    }

    private static function loadBundleEvents()
    {
        $event_dispatcher = self::$application->getContainer()->get('event.dispatcher');

        foreach (self::$bundles_config as $key => $config) {
            $recipe = self::getBundleLoaderRecipeFor($config, self::EVENT_RECIPE_KEY);
            if (null === $recipe) {
                $events = $config->getEventsConfig();
                if (false === is_array($events) || 0 === count($events)) {
                    continue;
                }

                $event_dispatcher->addListeners($events);
            } else {
                if (true === is_callable($recipe)) {
                    call_user_func_array($recipe, array(self::$application, $config));
                }
            }
        }
    }

    /**
     * Load every service definition defined in bundle
     */
    private static function loadBundlesServices()
    {
        $bundle_env_directory = null;
        if (BBApplication::DEFAULT_ENVIRONMENT !== self::$application->getEnvironment()) {
            $bundle_env_directory = implode(DIRECTORY_SEPARATOR, array(
                self::$application->getRepository(), 'Config', self::$application->getEnvironment(), 'bundle'
            ));
        }

        $container = self::$application->getContainer();
        foreach (self::$bundles_base_dir as $key => $dir) {
            $config = self::$application->getContainer()->get($key . '.config');
            $recipe = null;
            if (null !== $config) {
                $recipe = self::getBundleLoaderRecipeFor($config, self::SERVICE_RECIPE_KEY);
            }

            if (null === $recipe) {
                $services_directory = array($dir . DIRECTORY_SEPARATOR . 'Ressources');
                if (null !== $bundle_env_directory) {
                    $services_directory[] = $bundle_env_directory . DIRECTORY_SEPARATOR . basename($dir);
                }

                foreach ($services_directory as $sd) {
                    $filepath = $sd . DIRECTORY_SEPARATOR . 'services.xml';
                    if (true === is_file($filepath) && true === is_readable($filepath)) {
                        try {
                            ContainerBuilder::loadServicesFromXmlFile($container, $sd);
                        } catch (Exception $e) { /* nothing to do, just ignore it */ }
                    }
                }
            } else {
                if (null !== $config && true === is_callable($recipe)) {
                    call_user_func_array($recipe, array(self::$application, $config));
                }
            }
        }
    }

    private static function registerBundleClassContentDir()
    {
        foreach (self::$bundles_base_dir as $key => $dir) {
            $config = self::$application->getContainer()->get($key . '.config');
            $recipe = null;
            if (null !== $config) {
                $recipe = self::getBundleLoaderRecipeFor($config, self::SERVICE_RECIPE_KEY);
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
        foreach (self::$bundles_base_dir as $key => $dir) {
            $config = self::$application->getContainer()->get($key . '.config');
            $recipe = null;
            if (null !== $config) {
                $recipe = self::getBundleLoaderRecipeFor($config, self::SERVICE_RECIPE_KEY);
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
        foreach (self::$bundles_base_dir as $key => $dir) {
            $config = self::$application->getContainer()->get($key . '.config');
            $recipe = null;
            if (null !== $config) {
                $recipe = self::getBundleLoaderRecipeFor($config, self::SERVICE_RECIPE_KEY);
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
        $renderer = self::$application->getRenderer();
        foreach (self::$bundles_base_dir as $key => $dir) {
            $config = self::$application->getContainer()->get($key . '.config');
            $recipe = null;
            if (null !== $config) {
                $recipe = self::getBundleLoaderRecipeFor($config, self::SERVICE_RECIPE_KEY);
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

    public static function getBundleLoaderRecipeFor(Config $config, $key)
    {
        $recipe = null;
        $bundle_config = $config->getBundleConfig();
        if (true === isset($bundle_config['bundle_loader_recipes'])) {
            $recipe = true === isset($bundle_config['bundle_loader_recipes'][$key])
                ? $bundle_config['bundle_loader_recipes'][$key]
                : null
            ;
        }

        return $recipe;
    }
}
