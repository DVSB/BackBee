<?php

namespace BackBuilder\Bundle;

use ReflectionClass;

use BackBuilder\BBApplication,
	BackBuilder\DependencyInjection\ContainerBuilder;

use Symfony\Component\DependencyInjection\Definition,
    Symfony\Component\DependencyInjection\Reference,
    Symfony\Component\Yaml\Yaml;

class BundleLoader
{
	private static $bundleBaseDir = array();

	private static $bundlesConfig = array();

	private static $application = null;

	/**
	 * [loadBundlesIntoApplication description]
	 * @param  BBApplication $application  [description]
	 * @param  array         $bundleConfig [description]
	 * @return [type]                      [description]
	 */
	public static function loadBundlesIntoApplication(BBApplication $application, array $bundleConfig)
	{
		self::$application = $application;

		foreach ($bundleConfig as $name => $classname) {
            $key = 'bundle.' . strtolower($name);
            
            // Using ReflectionClass so we can read every bundle config file without the need to
            // instanciate each bundle's Config
            $r = new ReflectionClass($classname);
            self::$bundleBaseDir[$key] = dirname($r->getFileName());
            unset($r);

            $definition = new Definition($classname, array(new Reference('bbapp')));
            $definition->addTag('bundle');
            $application->getContainer()->setDefinition($key, $definition);
        }

        self::loadBundlesConfig();
        self::loadBundleRoutes();
        self::loadBundleEvents();
        self::loadBundlesServices();
        self::registerBundleClassContentDir();
        self::registerBundleResourceDir();
        self::registerBundleScriptDir();
        self::registerBundleHelperDir();

        // register bundle listener directory namespace
        $application->getContainer()->get('autoloader')->registerNamespace(
            'BackBuilder\Event\Listener',
            implode(DIRECTORY_SEPARATOR, array($application->getBBDir(), 'Bundle', 'Listener'))
        );

        // add BundleListener event (service.tagged.bundle)
        $application->getContainer()->get('event.dispatcher')->addListeners(array(
            'service.tagged.bundle' => array(
                'listeners' => array(
                    array(
                        'BackBuilder\Event\Listener\BundleListener',
                        'onGetBundleService'
                    )
                )
            ),
            'bbapplication.stop' => array(
                'listeners' => array(
                    array(
                        'BackBuilder\Event\Listener\BundleListener',
                        'onApplicationStop'
                    )
                )
            )
        ));

        // Cleaning memory
        self::$bundleBaseDir = null;
        self::$bundlesConfig = null;
        self::$application = null;
	}

	private static function loadBundlesConfig()
	{
		foreach (self::$bundleBaseDir as $key => $baseDir) {
			$filename = $baseDir . DIRECTORY_SEPARATOR . 'Ressources' . DIRECTORY_SEPARATOR . 'config.yml';
			if (true === is_readable($filename)) {
				self::$bundlesConfig[$key] = Yaml::parse($filename);
			}
		}
	}

	private static function loadBundleRoutes()
	{
		$controller = self::$application->getContainer()->get('controller');
		foreach (self::$bundlesConfig as $key => $config) {
			if (false === array_key_exists('route', $config)) {
				continue;
			}

			$controller->registerRoutes($key, $config['route']);
		}
	}

	private static function loadBundleEvents()
	{
		$eventDispatcher = self::$application->getContainer()->get('event.dispatcher');
		$autoloader = self::$application->getAutoloader();

		foreach (self::$bundlesConfig as $key => $config) {
			if (false === array_key_exists('events', $config)) {
				continue;
			}

			$eventDispatcher->addListeners($config['events']);
			$baseDir = self::$bundleBaseDir[$key] . DIRECTORY_SEPARATOR;
			$autoloader->registerNamespace(
                $baseDir . DIRECTORY_SEPARATOR . 'Listener', $baseDir . DIRECTORY_SEPARATOR . 'Listeners'
            );
		}
	}

    /**
     * Load every service definition defined in bundle
     */
    private static function loadBundlesServices()
    {
    	$container = self::$application->getContainer();
        foreach (self::$bundleBaseDir as $dir) {
            $xmlDir = $dir . DIRECTORY_SEPARATOR . 'Ressources' . DIRECTORY_SEPARATOR;
            if (true === is_file($xmlDir . 'services.xml')) {
                try {
                    ContainerBuilder::loadServicesFromXmlFile($container, $xmlDir);
                } catch (Exception $e) { /* nothing to do, just ignore it */ }
            }
        }
    }

	private static function registerBundleClassContentDir()
	{
		foreach (self::$bundleBaseDir as $dir) {
			$classContentDir = realpath($dir . DIRECTORY_SEPARATOR . 'ClassContent');
			if (false === $classContentDir) {
				continue;
			}

			self::$application->pushClassContentDir($classContentDir);
		}
	}

	private static function registerBundleResourceDir()
	{
		foreach (self::$bundleBaseDir as $dir) {
			$resourcesDir = realpath($dir . DIRECTORY_SEPARATOR . 'Ressources');
			if (false === $resourcesDir) {
				continue;
			}

			self::$application->pushResourceDir($resourcesDir);
		}
	}

	private static function registerBundleScriptDir()
	{
		$renderer = self::$application->getRenderer();
		foreach (self::$bundleBaseDir as $dir) {
			$scriptsDir = realpath($dir . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'scripts');
			if (false === $scriptsDir) {
				continue;
			}

			$renderer->addScriptDir($scriptsDir);
		}
	}

	private static function registerBundleHelperDir()
	{
		$renderer = self::$application->getRenderer();
		foreach (self::$bundleBaseDir as $dir) {
			$helperDir = realpath($dir . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'helpers');
			if (false === $helperDir) {
				continue;
			}

			$renderer->addHelperDir($helperDir);
		}
	}
}
