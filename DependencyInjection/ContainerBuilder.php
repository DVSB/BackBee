<?php

namespace BackBuilder\DependencyInjection;

use BackBuilder\BBApplication,
	BackBuilder\DependencyInjection\Container,
	BackBuilder\Exception\BBException;

use Symfony\Component\Config\FileLocator,
    Symfony\Component\DependencyInjection\Extension\ExtensionInterface,
	Symfony\Component\DependencyInjection\Loader\YamlFileLoader,
	Symfony\Component\DependencyInjection\Loader\XmlFileLoader,
	Symfony\Component\Yaml\Yaml;

class ContainerBuilder
{
	/**
	 * @var boolean
	 */
	private static $isInit = false;

	/**
	 * Current project's instance configuration (in repository/Config)
	 * @var array
	 */
	private static $config = array();

	/**
	 * @var BackBuilder\DependencyInjection\Container
	 */
	private static $container = null;

	/**
	 * @var BBApplication
	 */
	private static $application = null;

	/**
	 * 
	 * @param  BBApplication $application [description]
	 */
	public static function init(BBApplication $application)
	{
		// Retrieving config.yml without using Config service
		$filename = $application->getRepository() . DIRECTORY_SEPARATOR 
	    	. 'Config'  . DIRECTORY_SEPARATOR . 'config.yml';

        if (true === is_readable($filename)) {
            self::$config = Yaml::parse($filename);
        } else {
        	throw new \Exception();
        }

        self::$container = null;
        self::$application = $application;

        // Finally
        self::$isInit = true;
	}

	/**
	 * [getContainer description]
	 * @return [type] [description]
	 */
	public static function getContainer()
	{
		if (true === self::$isInit && null === self::$container) {
			self::buildContainer();
			self::initExternalBundleServices();
		} else {
			throw new BBException('You must call ContainerBuilder::init() before ContainerBuilder::getContainer() !');
		}

		self::$isInit = false;
		self::$config = array();
		self::$container = null;
		self::$application = null;

        return self::$container;
	}

	public static function loadServicesFromYamlFile(Container $container, $dir)
	{
		$loader = new YamlFileLoader($container, new FileLocator(array($dir)));
        $loader->load('services.yml');
	}

	public static function loadServicesFromXmlFile(Container $container, $dir)
	{
		$loader = new XmlFileLoader($container, new FileLocator(array($dir)));
        $loader->load('services.xml');
	}


	/**
	 * @return [type] [description]
	 */
	private static function buildContainer()
	{
		// Construct container
        self::$container = new Container();

        $dirToLookingFor = array();
        $dirToLookingFor[] = self::$application->getBBDir() . DIRECTORY_SEPARATOR . 'Config';
        $dirToLookingFor[] = self::$application->getBaseRepository() . DIRECTORY_SEPARATOR . 'Config';
        $dirToLookingFor[] = self::$application->getRepository() . DIRECTORY_SEPARATOR . 'Config';

        // Loop into every directory where we can potentially found a services.yml or services.xml
        foreach ($dirToLookingFor as $dir) {
            if (true === is_readable($dir . DIRECTORY_SEPARATOR . 'services.yml')) {
                self::loadServicesFromYamlFile(self::$container, $dir);
            } elseif (true === is_readable($dir . DIRECTORY_SEPARATOR . 'services.xml')) {
                self::loadServicesFromXmlFile(self::$container, $dir);
            }
        }

        self::initApplicationVarsIntoContainer();
	}

	private static function initApplicationVarsIntoContainer()
	{
		// Add BBApplication to container
        self::$container->set('bbapp', self::$application);

        // Set application others variables' values
        
        // define context
        self::$container->setParameter('bbapp.context', self::$application->getContext());

        // define cache directory
        $cachedir = self::$application->getBaseDir() . DIRECTORY_SEPARATOR . 'cache';
        if (true === isset($config['parameters']['cache_dir']) 
        	&& false === empty($config['parameters']['cache_dir'])) {
            $cachedir = $config['parameters']['cache_dir'];
        }

        self::$container->setParameter('bbapp.cache.dir', $cachedir);

        // define config directory
        self::$container->setParameter('bbapp.config.dir', self::$application->getConfigDir());

        // define repository directory
        self::$container->setParameter('bbapp.repository.dir', self::$application->getRepository());

        // define data directory
        $datadir = self::$application->getRepository() . DIRECTORY_SEPARATOR . 'Data';
        if (true === isset($config['parameters']['data_dir']) 
        	&& false === empty($config['parameters']['data_dir'])) {
            $datadir = $config['parameters']['data_dir'];
        }

        self::$container->setParameter('bbapp.data.dir', $datadir);

        //self::$container->setParameter('bbapp.cachecontrol.class', self::$application->getCacheProvider());
	}

	private static function initExternalBundleServices()
	{
		if (true === array_key_exists('external_bundles', self::$config)) {
			// Load external bundle services (Symfony2 Bundle)
	        $externalServices = self::$config['external_bundles'];
	        
	        if (null !== $externalServices && 0 < count($externalServices)) {
	            foreach ($externalServices as $key => $datas) {
	                $bundle = new $datas['class']();
	                if (false === ($bundle instanceof ExtensionInterface)) {
	                    $errorMsg = sprintf(
                            'BBApplication::_initContainer(): failed to load extension %s, it must implements `%s`', 
                            $datas['class'], 
                            'Symfony\Component\DependencyInjection\Extension\ExtensionInterface'
	                    );
	                    self::$debug($errorMsg);

	                    throw new BBException($errorMsg);
	                }

	                $config = true === isset($datas['config']) ? array($key => $datas['config']) : array();
	                $bundle->load($config, self::$_container);
	            }
	        }
		}
	}
}
